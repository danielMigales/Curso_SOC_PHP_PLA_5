<?php

//inicializar variables
const CHAR_CODE = "A1B2C3D4E5F6G7H8I9J0K1L2M3N4O5P6Q7R8S9T0U1V2W3X4Z5";

//inputs del formulario
$nombre =
	$email =
	$telefono =
	$comentario = $errores = null;

//variables para ficheros
$fichero = $_FILES['fichero'];
$nombreFichero = $_FILES['fichero']['name'];
$tipoFichero = $_FILES['fichero']['type'];
$longFichero = $_FILES['fichero']['size'];
$tmpNombre = $_FILES['fichero']['tmp_name'];
$destinoServidor = 'archivos';
$posicion = null;
$extension = null;

//array con las extensiones permitidas
$extensionesValidas = array('.jpg', '.jpeg', '.png', '.gif', 'svg');

//datos para el mail
const DESTINATARIO = 'destinatario@mail.com';
const ASUNTO = 'Correo desde formulario de contacto';
$fecha = date('d-m-Y');
$remitente = null;
$codigoConsulta = null;
$copiaCorreo = null;

// Incluir la libreria PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require '../PLA_5/PHPMailer-master/src/PHPMailer.php';
require '../PLA_5/PHPMailer-master/src/Exception.php';
require '../PLA_5/PHPMailer-master/src/SMTP.php';

//comprobar si se ha pulsado el botón de enviar
if (isset($_POST['enviar'])) {

	//valido inputs del formulario. Esta funcion tiene encadenadas las funciones siguientes
	validarInputs();

	//mediante una funcion obtengo un codigo aleatorio
	$codigoConsulta = generarCodigo();


	//guardar correo enviado en el archivo de log en formato csv;

	//confeccionar filas de la tabla con los correos enviados

}

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

		//recuperar telefono
		$telefono = filter_input(INPUT_POST, 'telefono', FILTER_VALIDATE_INT);

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
	//variables necesarias
	global $fichero,
		$nombreFichero, $tipoFichero, $longFichero, $tmpNombre, $destinoServidor, $posicion, $extension, $extensionesValidas, $errores;

	try {
		//detectar si se incluye un fichero en el input
		if ($_FILES['fichero']['error'] == 0) {

			//si sobrepasa el tamaño de kb lanzar excepcion
			if ($longFichero > 1000000000) {
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

	// Configurar parametros
	$mail = new PHPMailer(true); //instanciar objeto de la clase phpmailer
	$mail->Charset = 'utf8';
	$mail->isHTML(true);
	$mail->SMTPDebug = 1;

	//Remitente
	$mail->SetFrom($email, $nombre);
	$remitente = $nombre;

	// Destinatarios
	$mail->addAddress(DESTINATARIO);  // Email y nombre del destinatario

	// Contenido del correo
	$mail->Subject = ASUNTO;
	$mail->Body  =
		"<p><b>Fecha: </b><?=$fecha?></p><br>
		<p><b>Remitente: </b><?=$remitente?> </p><br>
		<p><b>Telefono: </b> <?=$telefono?></p><br>
		<p><b>Mensaje: </b><?=$comentario?></p><br>
		<p><b>Codigo Consulta: </b><?=$codigoConsulta?></p><br>
		<p><b>Nombre fichero: </b><?=$nombreFichero?></p><br>";
	$copiaCorreo = "<p><b>Fecha: </b><?=$fecha?></p><br>
		<p><b>Remitente: </b><?=$remitente?> </p><br>
		<p><b>Telefono: </b> <?=$telefono?></p><br>
		<p><b>Mensaje: </b><?=$comentario?></p><br>
		<p><b>Codigo Consulta: </b><?=$codigoConsulta?></p><br>
		<p><b>Nombre fichero: </b><?=$nombreFichero?></p><br>";


	//ESTO NO VALE PA NA
	if ($mail->send()) {
		$errores .= 'Correo enviado correctamente.';
	} else {
		$errores .= 'El envio de correo ha fallado.';
		//borrar el archivo en caso de error
		unlink("$destinoServidor/$nombreFichero");
	}
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
						<table></table>
					</div>
				</div>
			</div>
		</div>
	</div>
</body>

</html>