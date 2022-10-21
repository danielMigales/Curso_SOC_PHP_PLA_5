<?php
const CHAR_CODE = "A1B2C3D4E5F6G7H8I9J0K1L2M3N4O5P6Q7R8S9T0U1V2W3X4Z5";

//inicializar variables
//inputs del formulario
$nombre =
	$email =
	$telefono =
	$comentario = $errores = null;

//datos para el mail
const DESTINATARIO = 'destinatario@mail.com';
const ASUNTO = 'Correo desde formulario de contacto';

$fecha;
$remitente;
$codigoConsulta;



// Incluir la libreria PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require '../PLA_5/PHPMailer-master/src/PHPMailer.php';
require '../PLA_5/PHPMailer-master/src/Exception.php';
require '../PLA_5/PHPMailer-master/src/SMTP.php';

//comprobar si se ha pulsado el botón de enviar
if (isset($_POST['enviar'])) {

	//recuperar y validar datos obligatorios

	//recuperar nombre del input
	if (!$nombre = filter_input(INPUT_POST, 'nombre')) {
		$errores = "El nombre no puede estar vacio." . '<br>';
	}
	//ponerlo en minusculas
	$nombre = ucfirst(strtolower($nombre));

	//recuperar direccion email
	if (!$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL)) {
		$errores .= 'Email obligatorio' . '<br>';
	}

	//recuperar telefono
	if (!$telefono = filter_input(INPUT_POST, 'telefono', FILTER_VALIDATE_INT)) {
		$errores .= "Introduzca su numero de telefono" . '<br>';
	}

	//recuperar mensaje del input
	if (!$comentario = filter_input(INPUT_POST, 'comentario')) {
		$errores .= "El mensaje no puede estar vacio." . '<br>';
	}


	//si se ha seleccionado un fichero moverlo a la carpeta 'archivos'
	$fichero = $_FILES['fichero'];
	$nombreFichero = $_FILES['fichero']['name'];
	$tipoFichero = $_FILES['fichero']['type'];
	$longFichero = $_FILES['fichero']['size'];
	$tmpNombre = $_FILES['fichero']['tmp_name'];

	try {
		if ($_FILES['fichero']['error'] == 0) {
			if ($longFichero > 3900000) {
				throw new Exception("Archivo excede los 100Kb");
			}


			$posicion =  strrpos($nombreFichero, '.');
			$extension = substr($nombreFichero, $posicion);

			$extensionesValidas = array('.jpg', '.jpeg', '.png', '.gif');


			if (!in_array($extension, $extensionesValidas)) {
				throw new Exception("Solo se permiten este tipo de archivos");
			}
			else{
				echo 'archivo valido';
			}
			//$destinoServidor = 'c:';
			//move_uploaded_file($tmpNombre, $destinoServidor);
			echo 'archivo recibido';
		} else {
			echo 'no hay fichero';
		}
	} catch (Exception $e) {
		echo $e->getMessage();
	}

	//confeccionar y enviar mensaje de correo

	function mandarmail()
	{
		// Configurar parametros
		$mail = new PHPMailer(true); //instanciar objeto de la clase phpmailer
		$mail->Charset = 'utf8';
		$mail->isHTML(true);
		$mail->SMTPDebug = 1;

		//Remitente
		$mail->SetFrom($email, $nombre);

		// Destinatarios
		$mail->addAddress(DESTINATARIO);  // Email y nombre del destinatario

		// Contenido del correo
		$mail->Subject = ASUNTO;
		$mail->Body  = $comentario;
		$mail->AltBody = $comentario;

		//falta incluir:

		if ($mail->send()) {
			$errores = 'Correo enviado correctamente.';
		}
	}



	//guardar correo enviado en el archivo de log en formato csv;



	//confeccionar filas de la tabla con los correos enviados

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
					<div class='correo'>

					</div>
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