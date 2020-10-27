<?php
/**
 * @OA\Schema(
 *     schema="ping",
 *     type="object",
 *     @OA\Property(
 *      property="response",
 *      type="object",
 *      @OA\Property(
 *          property="result",
 *          description="success or error",
 *          type="string",
 *          example="success",
 *      ),
 *      @OA\Property(
 *          property="message",
 *          description="success or error message",
 *          type="string",
 *          example=null,
 *      ),
 *      @OA\Property(
 *          property="data",
 *          description="data from api",
 *          type="string",
 *          example="pong",
 *      ),
 *  ),
 * )
 */
/**
 * @OA\Schema(
 *     schema="status",
 *     type="object",
 *     @OA\Property(
 *      property="response",
 *      type="object",
 *      @OA\Property(
 *          property="result",
 *          description="success or error",
 *          type="string",
 *          example="success",
 *      ),
 *      @OA\Property(
 *          property="message",
 *          description="success or error message",
 *          type="string",
 *          example=null,
 *      ),
 *      @OA\Property(
 *          property="data",
 *          description="data from api",
 *          type="object",
 *          @OA\Property(
 *              property="status",
 *              description="success or error",
 *              type="string",
 *              example="ok",
 *          ),
 *          @OA\Property(
 *              property="api_version",
 *              type="string",
 *              example="2.0",
 *          ),
 *          @OA\Property(
 *              property="organizr_version",
 *              type="string",
 *              example="2.0.650",
 *          )
 *      ),
 *  ),
 * )
 */
/**
 * @OA\Schema(
 *     schema="pluginSettingsPage",
 *     type="object",
 *     @OA\Property(
 *      property="response",
 *      type="object",
 *      @OA\Property(
 *          property="result",
 *          description="success or error",
 *          type="string",
 *          example="success",
 *      ),
 *      @OA\Property(
 *          property="message",
 *          description="success or error message",
 *          type="string",
 *          example=null,
 *      ),
 *      @OA\Property(
 *          property="data",
 *          description="data from api",
 *          type="object",
 *          @OA\Property(
 *              property="settingsPageObjectItem1",
 *              type="object",
 *          ),
 *          @OA\Property(
 *              property="settingsPageObjectItem2",
 *              type="object",
 *          ),
 *          @OA\Property(
 *              property="settingsPageObjectItem3",
 *              type="object",
 *          )
 *      ),
 *  ),
 * )
 */
/**
 * @OA\Schema(
 *     schema="successNullData",
 *     type="object",
 *     @OA\Property(
 *      property="response",
 *      type="object",
 *      @OA\Property(
 *          property="result",
 *          description="success or error",
 *          type="string",
 *          example="success",
 *      ),
 *      @OA\Property(
 *          property="message",
 *          description="success message or error message",
 *          type="string",
 *          example=null,
 *      ),
 *      @OA\Property(
 *          property="data",
 *          description="data from api",
 *          type="string",
 *          example=null,
 *      ),
 *  ),
 * )
 */
/**
 * @OA\Schema(
 *     schema="success-message",
 *     type="object",
 *     @OA\Property(
 *      property="response",
 *      type="object",
 *      @OA\Property(
 *          property="result",
 *          description="success or error",
 *          type="string",
 *          example="success",
 *      ),
 *      @OA\Property(
 *          property="message",
 *          description="success message or error message",
 *          type="string",
 *          example="Successful message here",
 *      ),
 *      @OA\Property(
 *          property="data",
 *          description="data from api",
 *          type="string",
 *          example=null,
 *      ),
 *  ),
 * )
 */
/**
 * @OA\Schema(
 *     schema="error-message",
 *     type="object",
 *     @OA\Property(
 *      property="response",
 *      type="object",
 *      @OA\Property(
 *          property="result",
 *          description="success or error",
 *          type="string",
 *          example="error",
 *      ),
 *      @OA\Property(
 *          property="message",
 *          description="success message or error message",
 *          type="string",
 *          example="Error message here",
 *      ),
 *      @OA\Property(
 *          property="data",
 *          description="data from api",
 *          type="string",
 *          example=null,
 *      ),
 *  ),
 * )
 */
/**
 * @OA\Schema(
 *     schema="unauthorized-message",
 *     type="object",
 *     @OA\Property(
 *      property="response",
 *      type="object",
 *      @OA\Property(
 *          property="result",
 *          description="success or error",
 *          type="string",
 *          example="error",
 *      ),
 *      @OA\Property(
 *          property="message",
 *          description="success message or error message",
 *          type="string",
 *          example="User is not authorized",
 *      ),
 *      @OA\Property(
 *          property="data",
 *          description="data from api",
 *          type="string",
 *          example=null,
 *      ),
 *  ),
 * )
 */
/**
 * @OA\Schema(
 *     schema="php-mailer-email-list",
 *     type="object",
 *     @OA\Property(
 *      property="response",
 *      type="object",
 *      @OA\Property(
 *          property="result",
 *          description="success or error",
 *          type="string",
 *          example="success",
 *      ),
 *      @OA\Property(
 *          property="message",
 *          description="success or error message",
 *          type="string",
 *          example=null,
 *      ),
 *      @OA\Property(
 *          property="data",
 *          description="data from api",
 *          type="array",
 *          @OA\Items({
 *          @OA\Property(
 *              property="causefx",
 *              type="string",
 *              example="causefx@organizr.app",
 *          )
 * })
 *      ),
 *  ),
 * )
 */
/**
 * @OA\Schema(
 *     schema="config-items-example",
 *     type="object",
 *     description="list of config items to update",
 *      @OA\Property(
 *          property="branch",
 *          description="config item name",
 *          type="string",
 *          example="v2-master",
 *      ),
 *      @OA\Property(
 *          property="hideRegistration",
 *          type="boolean",
 *          example=false,
 *      ),
 *      @OA\Property(
 *          property="homepageUnifiAuth",
 *          type="string",
 *          example="1",
 *      ),
 * )
 */