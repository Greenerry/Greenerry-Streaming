<?php
require_once '../includes/config.php';
require_user_login();

$uid = current_user_id();
$err = '';
$ok = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['titulo'] ?? '');
    $type = $_POST['tipo'] ?? 'Single';
    $description = trim($_POST['descricao'] ?? '');
    $releaseDate = trim($_POST['data_lancamento'] ?? '');
    $trackTitles = $_POST['track_title'] ?? [];

    if ($title === '') {
        $err = 'O titulo do lancamento e obrigatorio.';
    } elseif (!in_array($type, ['Single', 'EP', 'Album'], true)) {
        $err = 'Tipo de lancamento invalido.';
    } elseif ($releaseDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $releaseDate)) {
        $err = 'A data de lancamento nao e valida.';
    }

    $cover = '';
    if (!$err && isset($_FILES['capa']) && $_FILES['capa']['error'] === UPLOAD_ERR_OK) {
        $coverExt = strtolower(pathinfo($_FILES['capa']['name'], PATHINFO_EXTENSION));
        if (!in_array($coverExt, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            $err = 'A capa deve estar em JPG, PNG ou WEBP.';
        } else {
            $cover = 'release_' . $uid . '_' . time() . '.' . $coverExt;
            $imageDir = __DIR__ . '/../assets/img/';
            if (!is_dir($imageDir)) {
                mkdir($imageDir, 0775, true);
            }
            move_uploaded_file($_FILES['capa']['tmp_name'], $imageDir . $cover);
        }
    }

    $tracks = [];
    if (!$err) {
        $audioDir = __DIR__ . '/../assets/audio/';
        if (!is_dir($audioDir)) {
            mkdir($audioDir, 0775, true);
        }

        if ($type === 'Single') {
            $singleTitle = trim($_POST['single_track_title'] ?? $title);
            if ($singleTitle === '') {
                $singleTitle = $title;
            }
            if (empty($_FILES['audio']['name'])) {
                $err = 'Tens de enviar o ficheiro de audio.';
            } else {
                $audioExt = strtolower(pathinfo($_FILES['audio']['name'], PATHINFO_EXTENSION));
                if (!in_array($audioExt, ['mp3', 'wav', 'ogg', 'flac', 'm4a'], true)) {
                    $err = 'Formato de audio nao suportado.';
                } else {
                    $audioName = 'track_' . $uid . '_' . time() . '_1.' . $audioExt;
                    move_uploaded_file($_FILES['audio']['tmp_name'], $audioDir . $audioName);
                    $tracks[] = ['title' => $singleTitle, 'audio' => $audioName];
                }
            }
        } else {
            $audioNames = $_FILES['tracks_audio']['name'] ?? [];
            $audioTmp = $_FILES['tracks_audio']['tmp_name'] ?? [];
            $audioErrors = $_FILES['tracks_audio']['error'] ?? [];

            foreach ($trackTitles as $index => $trackTitleRaw) {
                $trackTitle = trim($trackTitleRaw);
                $sourceName = $audioNames[$index] ?? '';
                $tmpName = $audioTmp[$index] ?? '';
                $errorCode = $audioErrors[$index] ?? UPLOAD_ERR_NO_FILE;

                if ($trackTitle === '' && $sourceName === '') {
                    continue;
                }
                if ($trackTitle === '' || $sourceName === '' || $errorCode !== UPLOAD_ERR_OK) {
                    $err = 'Cada faixa tem de ter titulo e ficheiro de audio.';
                    break;
                }

                $audioExt = strtolower(pathinfo($sourceName, PATHINFO_EXTENSION));
                if (!in_array($audioExt, ['mp3', 'wav', 'ogg', 'flac', 'm4a'], true)) {
                    $err = 'Foi encontrado um formato de audio nao suportado.';
                    break;
                }

                $audioName = 'track_' . $uid . '_' . time() . '_' . ($index + 1) . '.' . $audioExt;
                move_uploaded_file($tmpName, $audioDir . $audioName);
                $tracks[] = ['title' => $trackTitle, 'audio' => $audioName];
            }

            if (!$err && !$tracks) {
                $err = 'Adiciona pelo menos uma faixa ao lancamento.';
            }
        }
    }

    if (!$err) {
        $titleSafe = db_escape($conn, $title);
        $typeSafe = db_escape($conn, $type);
        $descriptionSafe = db_escape($conn, $description);
        $coverSafe = db_escape($conn, $cover);
        $dateSql = $releaseDate !== '' ? "'" . db_escape($conn, $releaseDate) . "'" : 'NULL';

        mysqli_begin_transaction($conn);
        try {
            mysqli_query(
                $conn,
                "INSERT INTO release_musical (idCliente, titulo, tipo, descricao, capa, data_lancamento, estado, ativo)
                 VALUES ({$uid}, '{$titleSafe}', '{$typeSafe}', '{$descriptionSafe}', '{$coverSafe}', {$dateSql}, 'pendente', 1)"
            );

            $releaseId = (int)mysqli_insert_id($conn);
            foreach ($tracks as $index => $track) {
                $trackTitleSafe = db_escape($conn, $track['title']);
                $audioSafe = db_escape($conn, $track['audio']);
                $trackNumber = $index + 1;
                mysqli_query(
                    $conn,
                    "INSERT INTO faixa (idRelease, numero_faixa, titulo, ficheiro_audio, estado, ativo)
                     VALUES ({$releaseId}, {$trackNumber}, '{$trackTitleSafe}', '{$audioSafe}', 'pendente', 1)"
                );
            }

            mysqli_commit($conn);
            $ok = 'Lancamento enviado para aprovacao do admin.';
        } catch (Throwable $e) {
            mysqli_rollback($conn);
            $err = 'Nao foi possivel guardar o lancamento.';
        }
    }
}

include '../includes/header.php';
?>

<section class="content-shell">
  <div class="wrap-sm">
    <div class="submission-hero submission-hero--music hero-card--single">
      <div class="submission-hero-copy">
        <span class="slabel">Submissao</span>
        <h2>Novo lancamento.</h2>
      </div>
    </div>

    <?php if ($err): ?>
      <div class="alert alert-err"><?= h($err) ?></div>
    <?php endif; ?>
    <?php if ($ok): ?>
      <div class="alert alert-ok"><?= h($ok) ?></div>
    <?php endif; ?>

    <div class="card surface-card surface-card--soft">
      <div class="card-body">
        <form method="post" enctype="multipart/form-data" class="stack-form" id="release-form">
          <div class="fg">
            <label class="flabel" for="titulo">Titulo do lancamento</label>
            <input id="titulo" type="text" name="titulo" class="finput" required maxlength="180">
          </div>

          <div class="frow">
            <div class="fg">
              <label class="flabel" for="tipo">Tipo</label>
              <select id="tipo" name="tipo" class="finput">
                <option value="Single">Single</option>
                <option value="EP">EP</option>
                <option value="Album">Album</option>
              </select>
            </div>
            <div class="fg">
              <label class="flabel" for="data_lancamento">Data de lancamento</label>
              <input id="data_lancamento" type="date" name="data_lancamento" class="finput">
            </div>
          </div>

          <div class="fg">
            <label class="flabel" for="descricao">Descricao</label>
            <textarea id="descricao" name="descricao" class="finput" maxlength="2000"></textarea>
          </div>

          <div class="fg">
            <label class="flabel" for="capa">Capa</label>
            <div class="upload-zone upload-zone--compact">
              <input id="capa" type="file" name="capa" class="finput" accept=".jpg,.jpeg,.png,.webp">
              <p>Imagem de capa em JPG, PNG ou WEBP.</p>
            </div>
          </div>

          <div id="single-fields" class="stack-form">
            <div class="fg">
              <label class="flabel" for="single_track_title">Titulo da faixa</label>
              <input id="single_track_title" type="text" name="single_track_title" class="finput" maxlength="180">
            </div>
            <div class="fg">
              <label class="flabel" for="audio">Ficheiro de audio</label>
              <div class="upload-zone upload-zone--compact">
                <input id="audio" type="file" name="audio" class="finput" accept=".mp3,.wav,.ogg,.flac,.m4a">
                <p>Formatos suportados: MP3, WAV, OGG, FLAC e M4A.</p>
              </div>
            </div>
          </div>

          <div id="multi-fields" class="stack-form is-hidden">
            <div id="track-list" class="stack-form"></div>
            <button type="button" class="btn btn-ghost" id="add-track">Adicionar faixa</button>
          </div>

          <button type="submit" class="btn btn-dark btn-full">Enviar para aprovacao</button>
        </form>
      </div>
    </div>
  </div>
</section>

<script>
const typeSelect = document.getElementById('tipo');
const singleFields = document.getElementById('single-fields');
const multiFields = document.getElementById('multi-fields');
const trackList = document.getElementById('track-list');
const addTrackButton = document.getElementById('add-track');
const releaseForm = document.getElementById('release-form');

function addTrackRow(index) {
  const row = document.createElement('div');
  row.className = 'frow';
  row.innerHTML = `
    <div class="fg">
      <label class="flabel">Titulo da faixa ${index + 1}</label>
      <input type="text" name="track_title[]" class="finput" maxlength="180">
    </div>
    <div class="fg">
      <label class="flabel">Audio da faixa ${index + 1}</label>
      <div class="upload-zone upload-zone--compact">
        <input type="file" name="tracks_audio[]" class="finput" accept=".mp3,.wav,.ogg,.flac,.m4a">
        <p>Escolhe o ficheiro audio da faixa.</p>
      </div>
    </div>
  `;
  trackList.appendChild(row);
}

function syncReleaseMode() {
  const isSingle = typeSelect.value === 'Single';
  singleFields.style.display = isSingle ? 'grid' : 'none';
  multiFields.style.display = isSingle ? 'none' : 'grid';

  if (!isSingle && trackList.children.length === 0) {
    addTrackRow(0);
    addTrackRow(1);
  }
}

addTrackButton?.addEventListener('click', () => addTrackRow(trackList.children.length));
typeSelect?.addEventListener('change', syncReleaseMode);

releaseForm?.addEventListener('submit', (event) => {
  if (typeSelect.value === 'Single') {
    if (!document.getElementById('audio')?.files?.length) {
      event.preventDefault();
      toast((localStorage.getItem('g_lang') || 'pt') === 'en'
        ? 'Choose an audio file for the release.'
        : 'Escolhe o ficheiro audio do lancamento.');
    }
    return;
  }

  const rows = Array.from(trackList.querySelectorAll('.frow'));
  const hasCompleteTrack = rows.some((row) => {
    const title = row.querySelector('input[type="text"]')?.value?.trim();
    const file = row.querySelector('input[type="file"]')?.files?.length;
    return title && file;
  });

  if (!hasCompleteTrack) {
    event.preventDefault();
    toast((localStorage.getItem('g_lang') || 'pt') === 'en'
      ? 'Add at least one track with title and audio.'
      : 'Adiciona pelo menos uma faixa com titulo e audio.');
  }
});

syncReleaseMode();
</script>

<?php include '../includes/footer.php'; ?>
