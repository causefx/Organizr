![OrganizrHeader](https://github.com/causefx/Organizr/raw/v2-develop/plugins/images/organizr/logo-wide.png)

[![Percentage of issues still open](http://isitmaintained.com/badge/open/causefx/Organizr.svg)](http://isitmaintained.com/project/causefx/Organizr "Percentage of issues still open")
[![Average time to resolve an issue](http://isitmaintained.com/badge/resolution/causefx/Organizr.svg)](http://isitmaintained.com/project/causefx/Organizr "Average time to resolve an issue")
[![GitHub stars](https://img.shields.io/github/stars/causefx/Organizr.svg)](https://github.com/causefx/Organizr/stargazers)
[![GitHub forks](https://img.shields.io/github/forks/causefx/Organizr.svg)](https://github.com/causefx/Organizr/network)
[![Docker pulls](https://img.shields.io/docker/pulls/organizr/organizr.svg)](https://hub.docker.com/r/organizr/organizr)
[![Donate](https://img.shields.io/badge/Donate-PayPal-green.svg)](https://paypal.me/causefx)
[![Beerpay](https://beerpay.io/causefx/Organizr/badge.svg?style=beer-square)](https://beerpay.io/causefx/Organizr)
[![Beerpay](https://beerpay.io/causefx/Organizr/make-wish.svg?style=flat-square)](https://beerpay.io/causefx/Organizr?focus=wish)

![OrganizrAbout](https://user-images.githubusercontent.com/16184466/53614282-a91e9e00-3b96-11e9-9b3e-d249775ecaa1.png)

Do you have quite a bit of services running on your computer or server? Do you have a lot of bookmarks or have to memorize a bunch of ip's and ports? Well, Organizr is here to help with that. Organizr allows you to setup "Tabs" that will be loaded all in one webpage. You can then work on your server with ease. Want to give users access to some Tabs? No problem, just enable user support and have them make an account. Want guests to be able to visit too? Enable Guest support for those tabs.

![OrganizrInfo](https://user-images.githubusercontent.com/16184466/53614285-a9b73480-3b96-11e9-835e-9fadd045582b.png)

- PHP 7.2+
- [Official Site](https://organizr.app) - Will be refreshed soon!
- [Official Discord](https://organizr.app/discord)

- [See Wiki](https://docs.organizr.app/) - Will be updated soon!
- [Docker](https://hub.docker.com/r/organizr/organizr)

![OrganizrGallery](https://user-images.githubusercontent.com/16184466/53614284-a9b73480-3b96-11e9-9bea-d7a30b294267.png)

<img src="https://user-images.githubusercontent.com/16184466/53615855-35cc5a80-3b9d-11e9-882b-f09f3eb18173.png" width="23%"></img>
<img src="https://user-images.githubusercontent.com/16184466/53615856-35cc5a80-3b9d-11e9-8428-1f2ae05da2c9.png" width="23%"></img>
<img src="https://user-images.githubusercontent.com/16184466/53615857-35cc5a80-3b9d-11e9-82bf-91987c529e72.png" width="23%"></img>
<img src="https://user-images.githubusercontent.com/16184466/53615858-35cc5a80-3b9d-11e9-8149-01a7fcd9160a.png" width="23%"></img>

[![OrganizrOverview](https://img.youtube.com/vi/LZL4smFB6wU/0.jpg)](https://www.youtube.com/watch?v=LZL4smFB6wU)

![OrganizrFeat](https://user-images.githubusercontent.com/16184466/53614283-a9b73480-3b96-11e9-90ef-6e752e067884.png)

- 'Forgot Password' support [receive an email with your new password, prerequisites: mail server setup]
- Additional language support
- Custom tabs for your services
- Customise the top bar by adding your own site logo or site name
- Enable or disable iFrame for your tabs
- Fail2ban support ([see wiki](https://docs.organizr.app/books/setup-features/page/fail2ban-integration))
- Fullscreen Support
- Gravatar Support
- Keyboard shortcut support (Check help tab in settings)
- Login with Plex/Emby/LDAP or sFTP credentials
- Mobile support
- Multiple login support
- Nginx Auth_Request support ([see wiki](https://docs.organizr.app/books/setup-features/page/serverauth))
- Organizr login log viewer
- Personalise any theme: Customise the look and feel of Organizr with access to the colour palette
- Pin/Unpin sidebar
- Protect new user account creation with registration password
- Quick access tabs (access your tabs quickly e.g. www.example.com/#Sonarr)
- Set default page on launch
- Theme-able
- Unlimited User Groups
- Upload new icons with ease
- User management support: Create, delete and promote users from the user management console
- Many more...

![OrganizrFeatReq](https://user-images.githubusercontent.com/16184466/53614286-a9b73480-3b96-11e9-8495-4944b85b1313.png)

[![Feature Requests](http://feathub.com/causefx/Organizr?format=svg)](http://feathub.com/causefx/Organizr)

![OrganizrDocker](https://user-images.githubusercontent.com/16184466/53667702-fcdcc600-3c2e-11e9-8828-860e531e8096.png)

[![Repository](https://img.shields.io/github/stars/organizr/docker-organizr?color=402885&style=for-the-badge&logo=github&logoColor=41add3&)](https://github.com/Organizr/docker-organizr)
[![GitHub Workflow Status](https://img.shields.io/github/workflow/status/organizr/docker-organizr/Build%20Container?color=402885&style=for-the-badge&logo=github&logoColor=41add3)](https://github.com/organizr/docker-organizr/actions?query=workflow%3A%22Build+Container%22)
[![Docker Pulls](https://img.shields.io/docker/pulls/organizr/organizr?color=402885&style=for-the-badge&logo=docker&logoColor=41add3)](https://hub.docker.com/r/organizr/organizr/)

##### Usage

```bash
docker create \
  --name=organizr \
  -v <path to data>:/config \
  -e PGID=<gid> -e PUID=<uid>  \
  -p 80:80 \
  -e fpm="false" `#optional` \
  -e branch="v2-master" `#optional` \
  organizr/organizr
```

##### Parameters

The parameters are split into two halves, separated by a colon, the left hand side representing the host and the right the container side. For example with a port -p external:internal - what this shows is the port mapping from internal to external of the container. So `-p 8080:80` would expose port 80 from inside the container to be accessible from the host's IP on port 8080 and `http://192.168.x.x:8080` would show you what's running INSIDE the container on port 80.

- `-p 80` - The port(s)
- `-v /config` - Mapping the config files for Organizr
- `-e PGID` Used for GroupID - see below for link
- `-e PUID` Used for UserID - see below for link

The optional parameters and GID and UID are described in the [readme](https://github.com/Organizr/docker-organizr#parameters) for the container.

##### Info

- Shell access whilst the container is running: `docker exec -it organizr /bin/bash`
- To monitor the logs of the container in realtime: `docker logs -f organizr`

![OrganizrSponsor](https://user-images.githubusercontent.com/16184466/53614287-a9b73480-3b96-11e9-9c8e-e32b4ae20c0d.png)

### BrowserStack for allowing us to use their platform for testing

[![BrowserStack](https://avatars2.githubusercontent.com/u/1119453?s=200&v=4g)](https://www.browserstack.com)

### This project is supported by

<img src="https://opensource.nyc3.cdn.digitaloceanspaces.com/attribution/assets/SVG/DO_Logo_horizontal_blue.svg" width="200px"></img>