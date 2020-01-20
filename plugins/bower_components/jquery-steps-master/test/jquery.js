(function ()
{
    var versionPattern = /^[\?|\&]{1}version=(\d\.\d\.\d|latest)&?$/,
        version = versionPattern.exec(location.search),
        defaultVersion = "1.11.1",
        file = "http://code.jquery.com/jquery-git.js";

    if (version != null && version.length > 0)
    {
        version = version[1];
    }
    else
    {
        version = defaultVersion;
    }

    if (version !== "latest")
    {
        file = "../lib/jquery-" + version + ".min.js";
    }

    document.write("<script src='" + file + "'></script>");
})();