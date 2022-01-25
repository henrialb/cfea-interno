<?php
// Initialize the session
session_start();
// Check if the user is logged in, if not then redirect him to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
  header("location: /login");
  exit;
} else {
  $user_id = $_SESSION['id'];
  // Load required files
  require "db_connect.php";
  require "functions.php";
  $access = user_access($user_id);
}

if(isset($_POST['action'])) {
  if($_POST['action'] == 'socio') {
    $num_socio = $_POST['num_socio'];
    // Ir buscar quota actual
    $socio = socio_info($num_socio);
    $quota_year = year($socio->quota);
    $quota_month = month($socio->quota);
  } else if($_POST['action'] == 'update_quota' && $access != 'sad') {
    $quota = compose_date($_POST['ano'], $_POST['mes']); // prepare date in the right format
    $alert = actualizar_quota($_POST['num_socio'], $quota); // result is an array with keys 'success', 'message'
  } else if($_POST['action'] == 'addUser') {
    $name = $_POST['name_socio'];
    $num_socio = create_socio($name);
    $socio = socio_info($num_socio);
    $quota_year = year($socio->quota);
    $quota_month = month($socio->quota);
  } else if($_POST['action'] == 'update_name') {
    $name = $_POST['name_socio'];
    $num_socio = $_POST['num_socio'];
    $alert = update_name($num_socio, $name);
  }
} ?>

<!DOCTYPE html>
<html lang="pt">
  <head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="utf-8">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KyZXEAg3QhqLMpG8r+8fhAXLRk2vvoC2f3B09zVXn8CA5QIVfZOJ3BCsw2P0p/We" crossorigin="anonymous">
    <!-- <link href="includes/css/style.css" rel="stylesheet"> -->
    <!-- <script src="https://kit.fontawesome.com/03a688feb9.js" crossorigin="anonymous"></script> -->
    <title>CFEA – Interno</title>
  </head>
  <body class="bg-light">
    <!-- Navbar -->
    <div class="bg-white border-bottom">
      <div class="container d-flex justify-content-between align-items-center py-2">
        <strong>CFEA</strong>
        <div>
          <a href="logout">Sair</a>
          <!-- <img src="https://cdn.onlinewebfonts.com/svg/img_184513.png" alt="" class="rounded-circle shadow-4" style="width: 34px;"> -->
        </div>
      </div>
    </div>

    <div class="container mt-3">
      <!-- Alert -->
      <?php
      if(isset($alert)) {
        $alert_type = $alert["success"] == true ? 'success' : 'danger'; ?>
        <div class="alert alert-<?php echo $alert_type ?>" role="alert">
          <?php echo $alert["message"] ?>
        </div><?php
        if($alert["success"] == false) {
          $num_socio = $alert["socio"];
          $socio = socio_info($num_socio);
          $quota_year = year($socio->quota);
          $quota_month = month($socio->quota);
        }
      }

      if(isset($num_socio)) { // ==> $_POST['action'] == 'socio' || $alert["succeess"] == false
        $quota = (new DateTime($socio->quota))->format('Y-m');
        $quota_accepted = (new DateTime('FIRST DAY OF PREVIOUS MONTH'))->format('Y-m');
        $quota_up_to_date = $quota >= $quota_accepted ? true : false; ?>

        <div class="row justify-content-center">
          <div class="col-12 col-sm-10 col-md-8">
            <div class="bg-white border rounded p-3 mb-3">
              <h3><?php echo $access != 'sad' ? $socio->name : name_initials($socio->name) ?></h3>
              <?php if ($access != 'sad') { ?>
                <p><a href="#" id="btn-update_name">Corrigir nome</a></p>
              <?php } ?>
              <p class="small text-secondary"><?php echo $socio->num ?></p>
              <?php if ($access != 'sad') { ?>
                <div id="update_name" style="display: none;">
                  <form class="" method="post">
                    <input hidden name="action" value="update_name">
                    <input hidden name="num_socio" value=<?php echo $socio->num ?>>
                    <input type="text" name="name_socio" value="<?php echo $socio->name ?>">
                    <button type="submit" class="btn btn-primary">Actualizar nome</button>
                  </form>
                </div>
              <?php } ?>
            </div>
          </div>
          <div class="col-12 col-sm-10 col-md-4">
            <div class="bg-white border p-3">
              <div class="d-flex justify-content-between align-items-center">
                <h4>Quota</h4><?php
                if($quota_up_to_date === true) {
                  if($quota == '9999-12') {?>
                    <span class="badge bg-info">Sócio Menor</span><?php
                  } elseif($quota == $quota_accepted) { ?>
                    <span class="badge bg-warning">Última</span><?php
                  } else { ?>
                  <span class="badge bg-success">Em dia</span><?php
                  }
                } else { ?>
                  <span class="badge bg-danger">Em atraso</span><?php
                } ?>
              </div>
              <hr>
              <!-- Formulário com quota actual para actualizar -->

              <?php if ($access != 'sad') { ?>
                <form class="" method="post">
                  <input hidden name="action" value="update_quota">
                  <input hidden name="num_socio" value=<?php echo $num_socio ?>>
                  <div class="form-group">
                    <div class="row">
                      <div class="col">
                        <label for="mes">Mês</label>
                        <select name="mes" class="form-control">
                          <?php
                          $i = 1;
                          while($i < $quota_month) { ?>
                            <option value="<?php echo month_num($i) ?>"><?php echo $i ?></option><?php
                            $i++;
                          }
                          ?>
                          <option value="<?php echo month_num($quota_month) ?>" selected><?php echo display_month($quota_month) ?></option>
                          <?php
                          $i++;
                          while($i <= 12) { ?>
                            <option value="<?php echo month_num($i) ?>"><?php echo $i ?></option><?php
                            $i++;
                          }
                          ?>
                        </select>
                      </div>
                      <div class="col">
                        <label for="ano">Ano</label>
                        <?php if ($quota_year != '9999') { ?>
                          <select name="ano" class="form-control">
                            <option value="<?php echo $quota_year - 1 ?>"><?php echo $quota_year - 1 ?></option>
                            <option value="<?php echo $quota_year ?>" selected><?php echo $quota_year ?></option>
                            <option value="<?php echo $quota_year + 1 ?>"><?php echo $quota_year + 1 ?></option>
                            <option value="<?php echo $quota_year + 2 ?>"><?php echo $quota_year + 2 ?></option>
                          </select>
                        <?php } else { ?>
                          <input type="text" name="ano" value="9999">
                        <?php } ?>
                      </div>
                    </div>
                  </div>
                  <div class="text-center mt-3">
                    <button type="submit" id="btn-enter" class="btn btn-primary w-100">Actualizar</button>
                  </div>
                </form>
              <?php } ?>
            </div>
          </div>
        </div><?php
      }
      else { ?>
        <h2>Actualizar quotas</h2>
        <!-- Form para número de sócio -->
        <form class="" method="post">
          <input hidden name="action" value="socio">
          <div class="form-group">
            <input type="text" name="num_socio" placeholder="Número de sócio">
          </div>
          <div class="text-center mt-3">
              <button type="submit" id="btn-enter" class="btn btn-primary btn-lg">Procurar</button>
          </div>
        </form>
        <hr>

        <?php
        if ($access != "sad") { ?>
          <h2>Criar sócio</h2>

          <form class="" method="post">
            <input hidden name="action" value="addUser">

            <div class="form-group">
              <input type="text" name="name_socio" placeholder="Nome">
            </div>
            <div class='form-group'>
              Vai ser o sócio numero: <b><?php echo max_num_socio() + 1 ?></b>
            </div>
            <div class="text-center mt-3">
                <button type="submit" id="btn-enter" class="btn btn-primary btn-lg">Gravar</button>
            </div>
          </form><?php
        }
      }

      if(isset($_POST['action'])) {
        echo "<br><a href='socios'>< Voltar</a>";
      } ?>
    </div>

    <script>
      document.getElementById("btn-update_name").addEventListener("click", function(){
        document.getElementById("update_name").style.display = "block";
      });
    </script>
  </body>
</html>
