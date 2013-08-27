<?php
Phar::mapPhar('/var/lib/jenkins/jobs/t41/workspace/tests/rapports/myproject.phar');
spl_autoload_register(function ($className) {
	$libPath = 'phar://T41-${BUILD_NUMBER}.phar/lib/';
	$classFile = str_replace('\\',DIRECTORY_SEPARATOR,$className).'.php';
	$classPath = $libPath.$classFile;
	if (file_exists($classPath)) {
		require($classPath);
	}
});
__HALT_COMPILER();

?>