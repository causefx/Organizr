<?php

namespace Adldap\Models;

use InvalidArgumentException;
use Adldap\Utilities;
use Adldap\Models\Concerns\HasMemberOf;
use Adldap\Models\Concerns\HasDescription;

/**
 * Class Group
 *
 * Represents an LDAP group (security / distribution).
 *
 * @package Adldap\Models
 */
class Group extends Entry
{
    use HasDescription, HasMemberOf;

    /**
     * Returns all users apart of the current group.
     *
     * @link https://msdn.microsoft.com/en-us/library/ms677097(v=vs.85).aspx
     *
     * @return \Illuminate\Support\Collection
     */
    public function getMembers()
    {
        $members = $this->getMembersFromAttribute($this->schema->member());

        if(count($members) === 0) {
            $members = $this->getPaginatedMembers();
        }

        return $this->newCollection($members);
    }

    /**
     * Returns the group's member names only.
     *
     * @return array
     */
    public function getMemberNames()
    {
        $members = [];

        $dns = $this->getAttribute($this->schema->member()) ?: [];

        foreach ($dns as $dn) {
            $exploded = Utilities::explodeDn($dn);

            if (array_key_exists(0, $exploded)) {
                $members[] = $exploded[0];
            }
        }

        return $members;
    }

    /**
     * Sets the groups members using an array of user DNs.
     *
     * @param array $entries
     *
     * @return $this
     */
    public function setMembers(array $entries)
    {
        $this->setAttribute($this->schema->member(), $entries);

        return $this;
    }

    /**
     * Adds multiple entries to the current group.
     *
     * @param array $members
     *
     * @return bool
     */
    public function addMembers(array $members)
    {
        $members = array_map(function ($member) {
            return $member instanceof Model
                ? $member->getDn()
                : $member;
        }, $members);

        $mod = $this->newBatchModification(
            $this->schema->member(),
            LDAP_MODIFY_BATCH_ADD,
            $members
        );

        return $this->addModification($mod)->save();
    }

    /**
     * Adds an entry to the current group.
     *
     * @param string|Entry $entry
     *
     * @throws InvalidArgumentException When the given entry is empty or contains no distinguished name.
     *
     * @return bool
     */
    public function addMember($entry)
    {
        $entry = ($entry instanceof Model ? $entry->getDn() : $entry);

        if (is_null($entry)) {
            throw new InvalidArgumentException(
                'Cannot add member to group. The members distinguished name cannot be null.'
            );
        }

        $mod = $this->newBatchModification(
            $this->schema->member(),
            LDAP_MODIFY_BATCH_ADD,
            [$entry]
        );

        return $this->addModification($mod)->save();
    }

    /**
     * Removes an entry from the current group.
     *
     * @param string|Entry $entry
     *
     * @throws InvalidArgumentException
     *
     * @return bool
     */
    public function removeMember($entry)
    {
        $entry = ($entry instanceof Model ? $entry->getDn() : $entry);

        if (is_null($entry)) {
            throw new InvalidArgumentException(
                'Cannot add member to group. The members distinguished name cannot be null.'
            );
        }

        $mod = $this->newBatchModification(
            $this->schema->member(),
            LDAP_MODIFY_BATCH_REMOVE,
            [$entry]
        );

        return $this->addModification($mod)->save();
    }

    /**
     * Removes all members from the current group.
     *
     * @return bool
     */
    public function removeMembers()
    {
        $mod = $this->newBatchModification(
            $this->schema->member(),
            LDAP_MODIFY_BATCH_REMOVE_ALL
        );

        return $this->addModification($mod)->save();
    }

    /**
     * Returns the group type integer.
     *
     * @link https://msdn.microsoft.com/en-us/library/ms675935(v=vs.85).aspx
     *
     * @return string
     */
    public function getGroupType()
    {
        return $this->getFirstAttribute($this->schema->groupType());
    }

    /**
     * Retrieves group members by the specified model
     * attribute using their distinguished name.
     *
     * @param $attribute
     *
     * @return array
     */
    protected function getMembersFromAttribute($attribute)
    {
        $members = [];

        $dns = $this->getAttribute($attribute) ?: [];

        foreach ($dns as $dn) {
            $member = $this->query->newInstance()->findByDn($dn);

            if ($member instanceof Model) {
                $members[] = $member;
            }
        }

        return $members;
    }

    /**
     * Retrieves members that are contained in a member range.
     *
     * @return array
     */
    protected function getPaginatedMembers()
    {
        $members = [];

        $keys = array_keys($this->attributes);

        // We need to filter out the model attributes so
        // we only retrieve the member range.
        $attributes = array_values(array_filter($keys, function ($key) {
            return strpos($key,'member;range') !== false;
        }));

        // We'll grab the member range key so we can run a
        // regex on it to determine the range.
        $key = reset($attributes);

        preg_match_all(
            '/member;range\=([0-9]{1,4})-([0-9*]{1,4})/',
            $key,
            $matches
        );

        if ($key && count($matches) == 3) {
            $to = $matches[2][0];

            $members = $this->getMembersFromAttribute($key);

            // If the query already included all member results (indicated
            // by the '*'), then we can return here. Otherwise we need
            // to continue on and retrieve the rest.
            if($to === '*') {
                return $members;
            }

            $from = $to + 1;

            // We'll determine the member range simply
            // by doubling the selected from value.
            $to = $from * 2;

            // We'll need to query for the current model again but with
            // a new range to retrieve the other members.
            $group = $this->query->newInstance()->findByDn(
                $this->getDn(),
                [$this->query->getSchema()->memberRange($from, $to)]
            );

            // Finally, we'll merge our current members
            // with the newly returned members.
            $members = array_merge(
                $members,
                $group->getMembers()->toArray()
            );
        }

        return $members;
    }
}
