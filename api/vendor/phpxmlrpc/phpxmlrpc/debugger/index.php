<?php

$query = '';
if (isset($_GET['run'])) {
    $path = parse_url($_GET['run']);
    if (isset($path['query'])) {
        $query = '?' . $path['query'];
    }
}

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">
<html lang="en">
<head>
    <link rel="icon" type="image/vnd.microsoft.icon" href="favicon.ico">
    <title><?php if (defined('DEFAULT_WSTYPE') && (DEFAULT_WSTYPE == 1 || DEFAULT_WSTYPE == 2)) echo 'JSON-RPC'; else echo 'XML-RPC'; ?> Debugger</title>
</head>
<frameset rows="360,*">
    <frame name="frmcontroller" src="controller.php<?php echo htmlspecialchars($query); ?>" marginwidth="0"
           marginheight="0" frameborder="0"/>
    <frame name="frmaction" src="action.php" marginwidth="0" marginheight="0" frameborder="0"/>
</frameset>
</html>
