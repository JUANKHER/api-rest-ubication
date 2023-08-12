<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
// Verificar si se recibieron datos en formato JSON
$data = json_decode(file_get_contents("php://input"), true);
include "config.php";
include "utils.php";
if (!$data) {
  header("HTTP/1.1 400 Bad Request");
  echo json_encode(array("message" => "Datos inválidos"));
  exit();
}

$dbConn =  connect($db);

/*
  listar todos los posts o solo uno
 */
$tag = $data["tag"];
if ($tag == 'iniciarsesion') {
  $email = $data["email"];
  $password = $data["password"];

  // Consulta para verificar las credenciales del usuario
  $sql = $dbConn->prepare("SELECT * FROM usuarios WHERE UPPER(correo) = UPPER(?) AND password = ?");
  $sql->execute([$email, $password]);
  $user = $sql->fetch(PDO::FETCH_ASSOC);

  if ($user) {
      // Credenciales válidas
      header("HTTP/1.1 200 OK");
      echo json_encode(array("message" => "Inicio de sesión exitoso", "user" => $user));
  } else {
      // Credenciales inválidas
      header("HTTP/1.1 401 Unauthorized");
      echo json_encode(array("message" => "Credenciales inválidas"));
  }
}

// Crear un nuevo post
if ($tag == 'registro') {
  $nombre = $data["nombre"];
  $email = $data["email"];
  $password = $data["password"];
  $phoneNumber = $data["phoneNumber"];

  // Verificar si el correo electrónico ya está registrado
  $sqlCheckEmail = "SELECT id FROM usuarios WHERE correo = :email";
  $stmtCheckEmail = $dbConn->prepare($sqlCheckEmail);
  $stmtCheckEmail->bindParam(':email', $email);
  $stmtCheckEmail->execute();

  if ($stmtCheckEmail->rowCount() > 0) {
      // El correo electrónico ya está registrado
      header("HTTP/1.1 409 Conflict");
      echo json_encode(array("message" => "El correo electrónico ya está registrado"));
  } else {
      // El correo electrónico no está registrado, realizar la inserción
      $sqlInsert = "INSERT INTO usuarios (correo, password, nombre, telefono)
                    VALUES (:email, :password, :nombre, :phoneNumber)";
      $stmtInsert = $dbConn->prepare($sqlInsert);
      $stmtInsert->bindParam(':email', $email);
      $stmtInsert->bindParam(':password', $password);
      $stmtInsert->bindParam(':nombre', $nombre);
      $stmtInsert->bindParam(':phoneNumber', $phoneNumber);

      if ($stmtInsert->execute()) {
          $postId = $dbConn->lastInsertId();
          if ($postId) {
              $response = array(
                  "message" => "Registro exitoso",
                  "id" => $postId,
                  "nombre" => $nombre,
                  "email" => $email,
                  "phoneNumber" => $phoneNumber
              );
              header("HTTP/1.1 201 Created");
              echo json_encode($response);
          }
      } else {
          header("HTTP/1.1 500 Internal Server Error");
          echo json_encode(array("message" => "Error al registrar el usuario"));
      }
  }
}
if ($tag == 'ubicacion')
{
    // Obtener valores de la solicitud JSON
    $latitude = $data["latitude"];
    $longitude = $data["longitude"];
    $fecha = $data["fecha"];
    $estado = $data["estado"];

    // Insertar la ubicación en la base de datos
    $sql = "INSERT INTO ubicaciones (latitude, longitude, fecha, estado) VALUES (?, ?, ?, ?)";
    $stmt = $dbConn->prepare($sql);
    $result = $stmt->execute([$latitude, $longitude, $fecha, $estado]);

    if ($result) {
      header("HTTP/1.1 201 Created");
      echo json_encode(array("message" => "Ubicación registrada exitosamente"));
  } else {
      header("HTTP/1.1 500 Internal Server Error");
      echo json_encode(array("message" => "Error al registrar la ubicación"));
  }
}

//Borrar
if ($_SERVER['REQUEST_METHOD'] == 'DELETE')
{
	$id = $_GET['id'];
  $statement = $dbConn->prepare("DELETE FROM usuarios where id=:id");
  $statement->bindValue(':id', $id);
  $statement->execute();
	header("HTTP/1.1 200 OK");
	exit();
}

//Actualizar
if ($_SERVER['REQUEST_METHOD'] == 'PUT')
{
    $input = $_GET;
    $postId = $input['id'];
    $fields = getParams($input);

    $sql = "
          UPDATE usuarios
          SET $fields
          WHERE id='$postId'
           ";

    $statement = $dbConn->prepare($sql);
    bindAllValues($statement, $input);

    $statement->execute();
    header("HTTP/1.1 200 OK");
    exit();
}


//En caso de que ninguna de las opciones anteriores se haya ejecutado
header("HTTP/1.1 400 Bad Request");

?>