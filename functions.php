<?php
class Socio {
  public $num;
  public $name;
  public $quota;

  function __construct($num, $name, $quota) {
    $this->num = $num;
    $this->name = $name;
    $this->quota = $quota;
  }
}

function compose_date($year, $month) {
  return "$year-$month-01";
}

function year($date) {
  return substr($date, 0, 4);
}

function month($date) {
  return substr($date, 5, 2);
}

function month_num($month) {
  return ($month < 10) ? '0'.$month : $month;
}

function display_month($month) {
  return ($month < 10) ? $month[1] : $month;
}

function socio_info($num) {
  global $mysqli;
  // Ler quota actual na base de dados
  $sql = "SELECT nome, quota FROM socios WHERE num = ?";
  $stmt = $mysqli->prepare($sql);
  $stmt->bind_param("i", $num);
  $stmt->execute();
  $stmt -> store_result();
  $stmt -> bind_result($name, $quota);
  $stmt -> fetch();

  $socio = new Socio($num, $name, $quota);
  return $socio;
}

// Mostrar apenas iniciais do nome
function name_initials($name) {
  $names = explode(" ", $name);
  $initials = "";
  foreach ($names as $name) {
    $initials .= $name[0]."*** ";
  }
  return trim($initials);
}

function user_access($id) {
  global $mysqli;
  $sql = "SELECT id, access FROM users WHERE id = ?";
  $stmt = $mysqli->prepare($sql);
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $stmt -> store_result();
  $stmt -> bind_result($id, $access);
  $stmt -> fetch();

  return $access;
}

function valid_date($date) {
  if (preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $date)) {
    return true;
  } else {
    return false;
  }
}

// Mês e ano de uma data -> '2021-09'
function mes_ano($date) {
  return substr($date, 0, 7);
}

function max_num_socio() {
  global $mysqli;
  $stmt = $mysqli->prepare("SELECT MAX(`num`) as max_socio FROM socios;");
  $stmt->execute();
  $stmt -> store_result();
  $stmt -> bind_result($ret);
  $stmt -> fetch();
  return $ret;
}

function create_socio($name) {
  global $mysqli;
  $socio_num = intval(max_num_socio()) + 1;

  $quota = date('Y-m-01');

  $sql = "INSERT INTO socios (quota, num, nome)
  VALUES (?,?,?)";

  $stmt = $mysqli->prepare($sql);
  $stmt->bind_param('sis', $quota, $socio_num, $name);
  $stmt->execute();
  $mysqli -> commit();

  return $socio_num;
}

function update_name($num_socio, $name) {
  if(empty($name)) {
    $result = array(
      "success" => false,
      "message" => "<strong>Erro!</strong> Campo do nome está vazio.",
      "socio" => $num_socio
    );
    return $result;
  } else {
    $current_name = socio_info($num_socio)->name;

    if($name == $current_name) {
      $result = array(
        "success" => false,
        "message" => "<strong>Erro!</strong> O nome é igual ao que já está no sistema.",
        "socio" => $num_socio
      );
      return $result;
    } else {
      global $user_id;
      global $mysqli;
      // Update quota in database
      $stmt = $mysqli->prepare("UPDATE socios SET nome = ? WHERE num = ?");

      if ($stmt === false) {
        trigger_error($this->mysqli->error, E_USER_ERROR);
        return;
      }

      /* Bind our params */
      $stmt->bind_param('si', $name, $num_socio);
      $status = $stmt->execute();

      /* BK: always check whether the execute() succeeded */
      if ($status === false) {
        trigger_error($stmt->error, E_USER_ERROR);
      }

      // Registar alteração no log da base de dados
      $log = array(
        "action" => "update",
        "field" => "name",
        "old_quota" => $current_name,
        "new_quota" => $name
      );

      $serialized_log = serialize($log);

      $sql = "INSERT INTO socios_updates (socio_id, log, user) VALUES (?, ?, ?)";
      $stmt = $mysqli->prepare($sql);
      $stmt->bind_param("isi", $num_socio, $serialized_log, $user_id);
      $stmt->execute();

      // Close db connection
      $stmt->close();
      $mysqli->close();

      $result = array(
        "success" => true,
        "message" => "Nome actualizado com sucesso."
      );
      return $result;
    }
  }
}

function actualizar_quota($num_socio, $nova_quota) {
  // Verificar se nova quota tem formato válido -> '2022-08-01'
  if(!valid_date($nova_quota)) {
    $result = array(
      "success" => false,
      "message" => "<strong>Erro!</strong> Quota inválida.",
      "socio" => $num_socio
    );
    return $result;
  }
  else {
    $quota_actual = socio_info($num_socio)->quota; // 0=> num_socio; 1=> nome; 2=> quota

    // Verificar se houve alteração à quota
    if(mes_ano($nova_quota) == mes_ano($quota_actual)) {
      $result = array(
        "success" => false,
        "message" => "<strong>Erro!</strong> Actualização falhou. Nova quota é igual à quota já registada no sistema.",
        "socio" => $num_socio
      );
      return $result;
    }
    else {
      global $user_id;
      global $mysqli;
      // Update quota in database
      $stmt = $mysqli->prepare("UPDATE socios SET quota = ? WHERE num = ?");

      if ($stmt === false) {
        trigger_error($this->mysqli->error, E_USER_ERROR);
        return;
      }

      /* Bind our params */
      $stmt->bind_param('si', $nova_quota, $num_socio);
      $status = $stmt->execute();

      /* BK: always check whether the execute() succeeded */
      if ($status === false) {
        trigger_error($stmt->error, E_USER_ERROR);
      }

      // Registar alteração no log da base de dados
      $log = array(
        "action" => "update",
        "field" => "quota",
        "old_quota" => $quota_actual,
        "new_quota" => $nova_quota
      );

      $serialized_log = serialize($log);

      $sql = "INSERT INTO socios_updates (socio_id, log, user) VALUES (?, ?, ?)";
      $stmt = $mysqli->prepare($sql);
      $stmt->bind_param("isi", $num_socio, $serialized_log, $user_id);
      $stmt->execute();

      // Close db connection
      $stmt->close();
      $mysqli->close();

      $result = array(
        "success" => true,
        "message" => "Quota actualizada com sucesso."
      );
      return $result;
    }
  }
}
