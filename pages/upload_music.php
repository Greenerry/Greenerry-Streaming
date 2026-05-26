<?php
require_once '../includes/config.php';
require_user_login();

$uid = current_user_id();
$err = '';
$ok = '';
$editId = (int)($_GET['edit'] ?? $_POST['release_id'] ?? 0);
$editRelease = null;
$editTracks = [];

if ($editId > 0) {
    // Artists can only edit their own submissions, unless the admin has blocked them.
    $editRelease = db_one($conn, "SELECT * FROM release_musical WHERE idRelease = {$editId} AND idCliente = {$uid} LIMIT 1");
    if (!$editRelease || (int)($editRelease['bloqueado_admin'] ?? 0) === 1) {
        header('Location: profile.php?tab=music');
        exit;
    }
    $editTracks = db_all($conn, "SELECT * FROM faixa WHERE idRelease = {$editId} ORDER BY numero_faixa ASC");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['titulo'] ?? '');
    $type = $_POST['tipo'] ?? 'Single';
    $description = trim($_POST['descricao'] ?? '');
    $releaseDate = trim($_POST['data_lancamento'] ?? '');
    $trackTitles = $_POST['track_title'] ?? [];

    $err = verify_csrf_request() ?? '';

    if (!$err && $title === '') {
        $err = tr('error.release_title_required');
    } elseif (!$err && !in_array($type, ['Single', 'EP', 'Album'], true)) {
        $err = tr('error.release_type_invalid');
    } elseif (!$err && ($err = required_field($description, 'A descrição', 'Description'))) {
    } elseif (!$err && ($err = required_field($releaseDate, 'A data de lançamento', 'Release date'))) {
    } elseif (!$err && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $releaseDate)) {
        $err = tr('error.release_date_invalid');
    }

    $oldCover = (string)($editRelease['capa'] ?? '');
    // Keep old files available unless the edit supplies replacements.
    $oldAudioFiles = array_values(array_unique(array_filter(array_map(
        static fn($track) => (string)($track['ficheiro_audio'] ?? ''),
        $editTracks
    ))));
    $cover = $oldCover;
    if (!$err && !$editRelease && empty($_FILES['capa']['name'])) {
        $err = current_lang() === 'en' ? 'Cover image is required.' : 'A capa é obrigatória.';
    }
    if (!$err && isset($_FILES['capa']) && $_FILES['capa']['error'] === UPLOAD_ERR_OK) {
        $err = validate_uploaded_image($_FILES['capa']);
        if (!$err) {
            [$savedCover, $saveErr] = save_uploaded_file($_FILES['capa'], 'img', 'release_' . $uid, ['jpg', 'jpeg', 'png', 'webp'], GREENERRY_MAX_IMAGE_BYTES);
            $err = $saveErr ?? '';
            if (!$err) {
                $cover = $savedCover;
            }
        }
    }

    $tracks = [];
    if (!$err) {
        $audioDir = __DIR__ . '/../assets/audio/';
        if (!is_dir($audioDir)) {
            mkdir($audioDir, 0775, true);
        }

        if ($type === 'Single') {
            // Single mode has one track row and one audio upload.
            $singleTitle = trim($_POST['single_track_title'] ?? $title);
            if ($singleTitle === '') {
                $err = current_lang() === 'en' ? 'Track title is required.' : 'O título da faixa é obrigatório.';
            }
            if (!$err && isset($_FILES['audio']['name']) && is_array($_FILES['audio']['name'])) {
                $err = current_lang() === 'en' ? 'A single can only have one audio file.' : 'Um single só pode ter um ficheiro de áudio.';
            } elseif (!$err && !$editRelease && empty($_FILES['audio']['name'])) {
                $err = tr('error.audio_required');
            } elseif (!$err && !empty($_FILES['audio']['name'])) {
                $err = validate_uploaded_audio($_FILES['audio']);
                if (!$err) {
                    [$audioName, $saveErr] = save_uploaded_file($_FILES['audio'], 'audio', 'track_' . $uid . '_1', ['mp3', 'wav', 'ogg', 'flac', 'm4a'], GREENERRY_MAX_AUDIO_BYTES);
                    $err = $saveErr ?? '';
                }
                if (!$err) {
                    $tracks[] = ['title' => $singleTitle, 'audio' => $audioName];
                }
            } elseif (!$err && $editRelease && $editTracks) {
                if (empty($editTracks[0]['ficheiro_audio'])) {
                    $err = tr('error.audio_required');
                } else {
                    $tracks[] = ['id' => (int)$editTracks[0]['idFaixa'], 'title' => $singleTitle, 'audio' => $editTracks[0]['ficheiro_audio']];
                }
            } elseif (!$err) {
                $err = tr('error.audio_required');
            }
        } else {
            // EP/Album mode accepts multiple independently titled tracks.
            $trackIds = $_POST['track_id'] ?? [];
            $audioNames = $_FILES['tracks_audio']['name'] ?? [];
            $audioTmp = $_FILES['tracks_audio']['tmp_name'] ?? [];
            $audioErrors = $_FILES['tracks_audio']['error'] ?? [];
            if (count($trackTitles) > GREENERRY_MAX_RELEASE_TRACKS) {
                $err = current_lang() === 'en'
                    ? 'A release can have at most ' . GREENERRY_MAX_RELEASE_TRACKS . ' tracks.'
                    : 'Um lançamento pode ter no máximo ' . GREENERRY_MAX_RELEASE_TRACKS . ' faixas.';
            }

            foreach ($trackTitles as $index => $trackTitleRaw) {
                if ($err) {
                    break;
                }
                $trackTitle = trim($trackTitleRaw);
                $trackId = (int)($trackIds[$index] ?? 0);
                $existingTrack = null;
                foreach ($editTracks as $row) {
                    if ((int)$row['idFaixa'] === $trackId) {
                        $existingTrack = $row;
                        break;
                    }
                }
                $sourceName = $audioNames[$index] ?? '';
                $tmpName = $audioTmp[$index] ?? '';
                $errorCode = $audioErrors[$index] ?? UPLOAD_ERR_NO_FILE;

                if ($trackTitle === '' && $sourceName === '' && !$existingTrack) {
                    continue;
                }
                if ($trackTitle === '') {
                    $err = tr('error.track_incomplete');
                    break;
                }

                if ($sourceName !== '') {
                    if ($errorCode !== UPLOAD_ERR_OK) {
                        $err = tr('error.track_incomplete');
                        break;
                    }

                    if (validate_uploaded_audio([
                        'error' => $errorCode,
                        'size' => filesize($tmpName),
                        'name' => $sourceName,
                        'tmp_name' => $tmpName
                    ])) {
                        $err = tr('error.track_format');
                        break;
                    }

                    [$audioName, $saveErr] = save_uploaded_file([
                        'error' => $errorCode,
                        'size' => filesize($tmpName),
                        'name' => $sourceName,
                        'tmp_name' => $tmpName
                    ], 'audio', 'track_' . $uid . '_' . ($index + 1), ['mp3', 'wav', 'ogg', 'flac', 'm4a'], GREENERRY_MAX_AUDIO_BYTES);
                    if ($saveErr) {
                        $err = $saveErr;
                        break;
                    }
                } elseif ($existingTrack && !empty($existingTrack['ficheiro_audio'])) {
                    $audioName = $existingTrack['ficheiro_audio'];
                } else {
                    $err = tr('error.track_incomplete');
                    break;
                }

                $tracks[] = ['id' => $trackId, 'title' => $trackTitle, 'audio' => $audioName];
            }

            if (!$err && !$tracks) {
                $err = tr('error.track_required');
            }
        }

        if (!$err && !$tracks) {
            $err = tr('error.track_required');
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
            if ($editRelease) {
                // Resubmitting sends the release back to the admin queue.
                mysqli_query(
                    $conn,
                    "UPDATE release_musical
                     SET titulo = '{$titleSafe}',
                         tipo = '{$typeSafe}',
                         descricao = '{$descriptionSafe}',
                         capa = '{$coverSafe}',
                         data_lancamento = {$dateSql},
                         estado = 'pendente',
                         ativo = 1,
                         motivo_rejeicao = NULL,
                         idAdminAprovacao = NULL,
                         aprovado_em = NULL
                     WHERE idRelease = {$editId} AND idCliente = {$uid}"
                );
                $releaseId = $editId;
                mysqli_query($conn, "DELETE FROM faixa WHERE idRelease = {$releaseId}");
            } else {
                mysqli_query(
                    $conn,
                    "INSERT INTO release_musical (idCliente, titulo, tipo, descricao, capa, data_lancamento, estado, ativo)
                     VALUES ({$uid}, '{$titleSafe}', '{$typeSafe}', '{$descriptionSafe}', '{$coverSafe}', {$dateSql}, 'pendente', 1)"
                );
                $releaseId = (int)mysqli_insert_id($conn);
            }

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
            delete_orphan_asset_file($conn, 'img', $oldCover);
            delete_orphan_asset_files($conn, 'audio', $oldAudioFiles);
            cleanup_unused_uploaded_assets($conn);
            $ok = $editRelease
                ? tr('success.release_updated')
                : tr('success.release_sent');
            if ($editRelease) {
                $editRelease = db_one($conn, "SELECT * FROM release_musical WHERE idRelease = {$editId} AND idCliente = {$uid} LIMIT 1");
                $editTracks = db_all($conn, "SELECT * FROM faixa WHERE idRelease = {$editId} ORDER BY numero_faixa ASC");
            }
        } catch (Throwable $e) {
            mysqli_rollback($conn);
            $err = tr('error.release_save');
        }
    }
}

include '../includes/header.php';
?>

<section class="content-shell upload-music-page">
  <div class="wrap-sm">
    <div class="submission-hero submission-hero--music hero-card--single">
      <div class="submission-hero-copy">
        <span class="slabel" data-t="upload_music_label">Submissao</span>
        <h2><?= $editRelease ? '<span data-t="upload_music_edit_title">Editar lançamento.</span>' : '<span data-t="upload_music_title">Novo lançamento.</span>' ?></h2>
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
          <?= csrf_input() ?>
          <?php if ($editRelease): ?>
            <input type="hidden" name="release_id" value="<?= (int)$editRelease['idRelease'] ?>">
          <?php endif; ?>
          <div class="fg">
            <label class="flabel" for="titulo" data-t="upload_music_release_title">Título do lançamento</label>
            <input id="titulo" type="text" name="titulo" class="finput" required maxlength="180" value="<?= h($editRelease['titulo'] ?? '') ?>">
          </div>

          <div class="frow">
            <div class="fg">
              <label class="flabel" for="tipo" data-t="upload_music_type">Tipo</label>
              <select id="tipo" name="tipo" class="finput">
                <option value="Single" data-release-type="Single" <?= ($editRelease['tipo'] ?? 'Single') === 'Single' ? 'selected' : '' ?>><?= h(release_type_label('Single')) ?></option>
                <option value="EP" data-release-type="EP" <?= ($editRelease['tipo'] ?? '') === 'EP' ? 'selected' : '' ?>><?= h(release_type_label('EP')) ?></option>
                <option value="Album" data-release-type="Album" <?= ($editRelease['tipo'] ?? '') === 'Album' ? 'selected' : '' ?>><?= h(release_type_label('Album')) ?></option>
              </select>
            </div>
            <div class="fg">
              <label class="flabel" for="data_lancamento" data-t="upload_music_date">Data de lançamento</label>
              <input id="data_lancamento" type="date" name="data_lancamento" class="finput" required value="<?= h($editRelease['data_lancamento'] ?? '') ?>">
            </div>
          </div>

          <div class="fg">
            <label class="flabel" for="descricao" data-t="upload_music_description">Descrição</label>
            <textarea id="descricao" name="descricao" class="finput" required maxlength="2000"><?= h($editRelease['descricao'] ?? '') ?></textarea>
          </div>

          <div class="fg">
            <label class="flabel" for="capa" data-t="upload_music_cover">Capa</label>
            <?php if ($editRelease && !empty($editRelease['capa'])): ?>
              <div class="edit-media-preview">
                <img src="<?= h(asset_url('img', $editRelease['capa'])) ?>" alt="<?= h($editRelease['titulo']) ?>">
                <span data-t="upload_music_current_cover">Capa atual</span>
              </div>
            <?php endif; ?>
            <div class="upload-zone upload-zone--compact">
              <input id="capa" type="file" name="capa" class="finput" accept=".jpg,.jpeg,.png,.webp" <?= $editRelease ? '' : 'required' ?>>
              <div class="image-upload-preview image-upload-preview--cover" id="cover-image-preview" hidden></div>
              <p data-t="upload_music_cover_help">Imagem de capa em JPG, PNG ou WEBP.</p>
            </div>
          </div>

          <div id="single-fields" class="stack-form">
            <div class="fg">
              <label class="flabel" for="single_track_title" data-t="upload_music_track_title">Título da faixa</label>
              <input id="single_track_title" type="text" name="single_track_title" class="finput" required maxlength="180" value="<?= h($editTracks[0]['titulo'] ?? '') ?>">
              <div id="single-audio-preview" class="audio-upload-preview is-hidden">
                <span></span>
                <div class="audio-preview-controls">
                  <button type="button" class="audio-preview-play" data-upload-t-play="upload_music_play">Tocar</button>
                  <small class="audio-preview-current">0:00</small>
                  <div class="audio-preview-bar"><div></div><em>0:00</em></div>
                  <small class="audio-preview-duration">0:00</small>
                </div>
                <audio preload="metadata"></audio>
              </div>
            </div>
            <div class="fg">
              <label class="flabel" for="audio" data-t="upload_music_audio">Ficheiro de áudio</label>
              <div class="upload-zone upload-zone--compact">
                <input id="audio" type="file" name="audio" class="finput" accept=".mp3,.wav,.ogg,.flac,.m4a" <?= ($editRelease && !empty($editTracks[0]['ficheiro_audio'])) ? '' : 'required' ?>>
                <p data-t="upload_music_audio_help">Formatos suportados: MP3, WAV, OGG, FLAC e M4A. Podes arrastar um ou vários ficheiros.</p>
              </div>
            </div>
          </div>

          <div id="multi-fields" class="stack-form is-hidden">
            <div id="multi-audio-drop" class="upload-zone upload-zone--compact upload-zone--batch" tabindex="0">
              <strong data-t="upload_music_multi_drop_title">Arrasta vários ficheiros áudio aqui</strong>
              <span data-t="upload_music_multi_drop_text">Cada ficheiro fica numa faixa separada, depois podes mudar os nomes.</span>
            </div>
            <div id="track-list" class="stack-form"></div>
            <button type="button" class="btn btn-ghost" id="add-track" data-t="upload_music_add_track">Adicionar faixa</button>
          </div>

          <button type="submit" class="btn btn-dark btn-full"><?= $editRelease ? '<span data-t="upload_music_save_review">Guardar e enviar para revisão</span>' : '<span data-t="upload_music_submit">Enviar para aprovacao</span>' ?></button>
          <?php if ($editRelease): ?>
            <a href="profile.php?tab=music" class="btn btn-ghost btn-full" data-t="upload_music_back_profile">Voltar ao perfil</a>
          <?php endif; ?>
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
const multiAudioDrop = document.getElementById('multi-audio-drop');
const singleAudioPreview = document.getElementById('single-audio-preview');
const coverInput = document.getElementById('capa');
const coverImagePreview = document.getElementById('cover-image-preview');
const existingTracks = <?= json_encode(array_map(static function ($track) {
    return [
        'id' => (int)$track['idFaixa'],
        'title' => (string)$track['titulo'],
        'audio' => (string)$track['ficheiro_audio'],
        'audioUrl' => !empty($track['ficheiro_audio']) ? asset_url('audio', $track['ficheiro_audio']) : ''
    ];
}, $editTracks), JSON_UNESCAPED_UNICODE) ?>;
const editingRelease = <?= $editRelease ? 'true' : 'false' ?>;
const existingSingleAudio = existingTracks[0]?.audio || '';

function uploadLang() {
  return (localStorage.getItem('g_lang') || 'pt').startsWith('en') ? 'en' : 'pt';
}

function uploadText(key) {
  const active = uploadLang();
  if (typeof T !== 'undefined' && T[active]?.[key]) {
    return T[active][key];
  }
  const fallback = {
    upload_music_track_title_dynamic: active === 'en' ? 'Track title' : 'Título da faixa',
    upload_music_track_audio_dynamic: active === 'en' ? 'Track audio' : 'Audio da faixa',
    upload_music_current_file: active === 'en' ? 'Current file' : 'Ficheiro atual',
    upload_music_replace_audio_help: active === 'en' ? 'Choose a new audio file only if you want to replace it.' : 'Escolhe novo audio so se quiseres substituir.',
    upload_music_choose_drop_audio_help: active === 'en' ? 'Choose or drop the track audio file.' : 'Escolhe ou arrasta o ficheiro áudio da faixa.',
    upload_music_preview: active === 'en' ? 'Preview' : 'Previa',
    upload_music_play: active === 'en' ? 'Play' : 'Tocar',
    upload_music_pause: active === 'en' ? 'Pause' : 'Pausar',
    upload_music_remove_track: active === 'en' ? 'Remove track' : 'Remover faixa',
    upload_music_choose_audio_toast: active === 'en' ? 'Choose an audio file for the release.' : 'Escolhe o ficheiro áudio do lançamento.',
    upload_music_need_track_toast: active === 'en' ? 'Add at least one track with title and audio.' : 'Adiciona pelo menos uma faixa com titulo e audio.'
  };
  return fallback[key] || key;
}

function applyUploadMusicLanguage() {
  document.querySelectorAll('[data-upload-t]').forEach((element) => {
    element.textContent = uploadText(element.dataset.uploadT);
  });
  document.querySelectorAll('[data-upload-t-prefix]').forEach((element) => {
    element.textContent = `${uploadText(element.dataset.uploadTPrefix)} ${element.dataset.trackNumber || ''}`.trim();
  });
  document.querySelectorAll('[data-upload-t-current-file]').forEach((element) => {
    element.textContent = `${uploadText('upload_music_current_file')}: ${element.dataset.fileName || ''}`;
  });
  document.querySelectorAll('[data-upload-t-play]').forEach((button) => {
    const audio = button.closest('.audio-upload-preview')?.querySelector('audio');
    button.textContent = audio && !audio.paused
      ? uploadText('upload_music_pause')
      : uploadText('upload_music_play');
  });
  document.querySelectorAll('.audio-upload-preview span').forEach((label) => {
    label.dataset.previewLabel = uploadText('upload_music_preview');
  });
}

function refreshTrackRows() {
  Array.from(trackList.children).forEach((row, index) => {
    row.querySelectorAll('[data-track-number]').forEach((element) => {
      element.dataset.trackNumber = index + 1;
    });
  });
  applyUploadMusicLanguage();
}

function addTrackRow(index, track = null) {
  const row = document.createElement('div');
  row.className = 'frow upload-track-row';
  row.innerHTML = `
    <div class="fg">
      <label class="flabel" data-upload-t-prefix="upload_music_track_title_dynamic" data-track-number="${index + 1}">${uploadText('upload_music_track_title_dynamic')} ${index + 1}</label>
      <input type="hidden" name="track_id[]" value="${track?.id || 0}">
      <input type="text" name="track_title[]" class="finput" required maxlength="180" value="${(track?.title || '').replace(/"/g, '&quot;')}">
      <div class="audio-upload-preview is-hidden">
        <span></span>
        <div class="audio-preview-controls">
          <button type="button" class="audio-preview-play" data-upload-t-play="upload_music_play">${uploadText('upload_music_play')}</button>
          <small class="audio-preview-current">0:00</small>
          <div class="audio-preview-bar"><div></div><em>0:00</em></div>
          <small class="audio-preview-duration">0:00</small>
        </div>
        <audio preload="metadata"></audio>
      </div>
    </div>
    <div class="fg">
      <label class="flabel" data-upload-t-prefix="upload_music_track_audio_dynamic" data-track-number="${index + 1}">${uploadText('upload_music_track_audio_dynamic')} ${index + 1}</label>
      ${track?.audio ? `<p class="form-note" data-upload-t-current-file="1" data-file-name="${track.audio}">${uploadText('upload_music_current_file')}: ${track.audio}</p>` : ''}
      <div class="upload-zone upload-zone--compact">
        <input type="file" name="tracks_audio[]" class="finput" accept=".mp3,.wav,.ogg,.flac,.m4a" ${track?.audio ? '' : 'required'}>
        <p data-upload-t="${editingRelease ? 'upload_music_replace_audio_help' : 'upload_music_choose_drop_audio_help'}">${editingRelease ? uploadText('upload_music_replace_audio_help') : uploadText('upload_music_choose_drop_audio_help')}</p>
      </div>
    </div>
    <button type="button" class="btn btn-ghost btn-sm upload-track-remove" data-upload-t="upload_music_remove_track">${uploadText('upload_music_remove_track')}</button>
  `;
  row.querySelector('.upload-track-remove')?.addEventListener('click', () => {
    row.querySelectorAll('.audio-upload-preview').forEach((preview) => {
      const audio = preview.querySelector('audio');
      if (audio) {
        audio.pause();
        audio.removeAttribute('src');
        audio.load();
      }
      if (preview.dataset.objectUrl) {
        URL.revokeObjectURL(preview.dataset.objectUrl);
      }
    });
    row.remove();
    refreshTrackRows();
  });
  trackList.appendChild(row);
  bindDropZone(row.querySelector('.upload-zone'));
  if (track?.audioUrl) {
    showExistingAudioPreview(row.querySelector('.audio-upload-preview'), track);
  }
  refreshTrackRows();
  return row;
}

function syncReleaseMode() {
  const isSingle = typeSelect.value === 'Single';
  singleFields.style.display = isSingle ? 'grid' : 'none';
  multiFields.style.display = isSingle ? 'none' : 'grid';

  if (!isSingle && trackList.children.length === 0) {
    if (editingRelease && existingTracks.length) {
      existingTracks.forEach((track, index) => addTrackRow(index, track));
      return;
    }
    addTrackRow(0);
    addTrackRow(1);
  }
}

addTrackButton?.addEventListener('click', () => {
  if (trackList.children.length >= <?= (int)GREENERRY_MAX_RELEASE_TRACKS ?>) {
    toast(uploadLang() === 'en'
      ? 'A release can have at most <?= (int)GREENERRY_MAX_RELEASE_TRACKS ?> tracks.'
      : 'Um lançamento pode ter no máximo <?= (int)GREENERRY_MAX_RELEASE_TRACKS ?> faixas.');
    return;
  }
  addTrackRow(trackList.children.length);
});
typeSelect?.addEventListener('change', syncReleaseMode);

function audioTitleFromFile(file) {
  return file.name.replace(/\.[^/.]+$/, '').replace(/[_-]+/g, ' ').trim();
}

function fileListFrom(file) {
  const transfer = new DataTransfer();
  transfer.items.add(file);
  return transfer.files;
}

function attachFileToInput(input, file) {
  if (!input || !file) return;
  input.files = fileListFrom(file);
  input.dispatchEvent(new Event('change', { bubbles: true }));
}

function clearImagePreview(preview) {
  if (!preview) return;
  if (preview.dataset.objectUrl) {
    URL.revokeObjectURL(preview.dataset.objectUrl);
    delete preview.dataset.objectUrl;
  }
  preview.innerHTML = '';
  preview.hidden = true;
}

function updateCoverPreview() {
  const file = coverInput?.files?.[0];
  if (!coverImagePreview) return;
  clearImagePreview(coverImagePreview);
  if (!file || !file.type.startsWith('image/')) return;

  const image = document.createElement('img');
  const objectUrl = URL.createObjectURL(file);
  coverImagePreview.dataset.objectUrl = objectUrl;
  image.src = objectUrl;
  image.alt = file.name;
  coverImagePreview.appendChild(image);
  coverImagePreview.hidden = false;
}

function audioPreviewMarkup() {
  return `
    <span></span>
    <div class="audio-preview-controls">
      <button type="button" class="audio-preview-play" data-upload-t-play="upload_music_play">${uploadText('upload_music_play')}</button>
      <small class="audio-preview-current">0:00</small>
      <div class="audio-preview-bar"><div></div><em>0:00</em></div>
      <small class="audio-preview-duration">0:00</small>
    </div>
    <audio preload="metadata"></audio>
  `;
}

function formatPreviewTime(seconds) {
  if (!Number.isFinite(seconds)) return '0:00';
  const total = Math.max(0, Math.floor(seconds));
  const minutes = Math.floor(total / 60);
  return `${minutes}:${String(total % 60).padStart(2, '0')}`;
}

function bindAudioPreview(preview) {
  if (!preview || preview.dataset.previewBound === '1') return;
  preview.dataset.previewBound = '1';

  const audio = preview.querySelector('audio');
  const button = preview.querySelector('.audio-preview-play');
  const current = preview.querySelector('.audio-preview-current');
  const duration = preview.querySelector('.audio-preview-duration');
  const bar = preview.querySelector('.audio-preview-bar');
  const fill = preview.querySelector('.audio-preview-bar div');
  const hoverTime = preview.querySelector('.audio-preview-bar em');

  const sync = () => {
    const length = audio.duration || 0;
    const position = audio.currentTime || 0;
    if (current) current.textContent = formatPreviewTime(position);
    if (duration) duration.textContent = formatPreviewTime(length);
    if (fill) fill.style.width = length ? `${Math.min(100, (position / length) * 100)}%` : '0%';
    if (button) button.textContent = audio.paused ? uploadText('upload_music_play') : uploadText('upload_music_pause');
  };

  const seekFromEvent = (event) => {
    if (!audio.duration || !bar) return;
    const rect = bar.getBoundingClientRect();
    const ratio = Math.max(0, Math.min(1, (event.clientX - rect.left) / rect.width));
    audio.currentTime = ratio * audio.duration;
    sync();
  };

  button?.addEventListener('click', () => {
    if (audio.paused) audio.play().catch(() => {});
    else audio.pause();
  });
  audio.addEventListener('loadedmetadata', sync);
  audio.addEventListener('timeupdate', sync);
  audio.addEventListener('play', sync);
  audio.addEventListener('pause', sync);

  bar?.addEventListener('click', seekFromEvent);
  bar?.addEventListener('pointermove', (event) => {
    if (!audio.duration || !hoverTime) return;
    const rect = bar.getBoundingClientRect();
    const ratio = Math.max(0, Math.min(1, (event.clientX - rect.left) / rect.width));
    hoverTime.textContent = formatPreviewTime(ratio * audio.duration);
    hoverTime.style.left = `${ratio * 100}%`;
    if (bar.dataset.seeking === '1') {
      seekFromEvent(event);
    }
  });
  bar?.addEventListener('pointerdown', (event) => {
    bar.dataset.seeking = '1';
    seekFromEvent(event);
    bar.setPointerCapture?.(event.pointerId);
  });
  bar?.addEventListener('pointerup', () => {
    bar.dataset.seeking = '0';
  });
  bar?.addEventListener('pointercancel', () => {
    bar.dataset.seeking = '0';
  });
}

function showExistingAudioPreview(preview, track) {
  if (!preview || !track?.audioUrl) return;
  bindAudioPreview(preview);
  preview.classList.remove('is-hidden');
  preview.querySelector('span').textContent = track.audio || '';
  preview.querySelector('audio').src = track.audioUrl;
  preview.querySelector('.audio-preview-current').textContent = '0:00';
  preview.querySelector('.audio-preview-duration').textContent = '0:00';
  preview.querySelector('.audio-preview-bar div').style.width = '0%';
  preview.querySelector('.audio-preview-play').textContent = uploadText('upload_music_play');
}

function updateAudioPreview(input) {
  if (!input?.accept?.includes('.mp3')) return;
  const row = input.closest('.frow');
  const titleGroup = row?.querySelector('.fg:first-child');
  const zone = input.closest('.upload-zone');
  const file = input.files?.[0];
  if (!file) return;

  let preview = input.id === 'audio'
    ? singleAudioPreview
    : titleGroup?.querySelector('.audio-upload-preview') || zone?.querySelector('.audio-upload-preview');
  if (!preview && titleGroup) {
    preview = document.createElement('div');
    preview.className = 'audio-upload-preview';
    preview.innerHTML = audioPreviewMarkup();
    titleGroup.appendChild(preview);
  }
  if (!preview) {
    preview = document.createElement('div');
    preview.className = 'audio-upload-preview';
    preview.innerHTML = audioPreviewMarkup();
    zone?.appendChild(preview);
  }
  bindAudioPreview(preview);

  if (preview.dataset.objectUrl) {
    URL.revokeObjectURL(preview.dataset.objectUrl);
  }

  const objectUrl = URL.createObjectURL(file);
  preview.dataset.objectUrl = objectUrl;
  preview.classList.remove('is-hidden');
  preview.querySelector('span').textContent = file.name;
  preview.querySelector('audio').src = objectUrl;
  preview.querySelector('.audio-preview-current').textContent = '0:00';
  preview.querySelector('.audio-preview-duration').textContent = '0:00';
  preview.querySelector('.audio-preview-bar div').style.width = '0%';
  preview.querySelector('.audio-preview-play').textContent = uploadText('upload_music_play');
}

function fillMultiTracks(files) {
  let audioFiles = Array.from(files).filter((file) => /\.(mp3|wav|ogg|flac|m4a)$/i.test(file.name));
  if (!audioFiles.length) return;
  if (audioFiles.length > <?= (int)GREENERRY_MAX_RELEASE_TRACKS ?>) {
    toast(uploadLang() === 'en'
      ? 'A release can have at most <?= (int)GREENERRY_MAX_RELEASE_TRACKS ?> tracks.'
      : 'Um lançamento pode ter no máximo <?= (int)GREENERRY_MAX_RELEASE_TRACKS ?> faixas.');
    audioFiles = audioFiles.slice(0, <?= (int)GREENERRY_MAX_RELEASE_TRACKS ?>);
  }

  typeSelect.value = audioFiles.length === 1 ? typeSelect.value : 'EP';
  syncReleaseMode();
  if (typeSelect.value === 'Single' && audioFiles.length === 1) {
    attachFileToInput(document.getElementById('audio'), audioFiles[0]);
    const titleInput = document.getElementById('single_track_title');
    if (titleInput && !titleInput.value.trim()) {
      titleInput.value = audioTitleFromFile(audioFiles[0]);
    }
    return;
  }

  typeSelect.value = 'EP';
  syncReleaseMode();
  trackList.innerHTML = '';
  audioFiles.forEach((file, index) => {
    const row = addTrackRow(index);
    const titleInput = row.querySelector('input[name="track_title[]"]');
    const fileInput = row.querySelector('input[type="file"]');
    if (titleInput) titleInput.value = audioTitleFromFile(file);
    attachFileToInput(fileInput, file);
  });
}

function bindDropZone(zone) {
  if (!zone || zone.dataset.dropBound === '1') return;
  zone.dataset.dropBound = '1';
  const input = zone.querySelector('input[type="file"]');
  const isAudioInput = input?.accept?.includes('.mp3');

  ['dragenter', 'dragover'].forEach((eventName) => {
    zone.addEventListener(eventName, (event) => {
      event.preventDefault();
      zone.classList.add('is-dragging');
    });
  });

  ['dragleave', 'drop'].forEach((eventName) => {
    zone.addEventListener(eventName, (event) => {
      event.preventDefault();
      zone.classList.remove('is-dragging');
    });
  });

  zone.addEventListener('drop', (event) => {
    const files = event.dataTransfer?.files || [];
    if (!files.length) return;
    if (isAudioInput && (input?.id === 'audio' || files.length > 1)) {
      fillMultiTracks(files);
      return;
    }
    attachFileToInput(input, files[0]);
  });

  input?.addEventListener('change', () => {
    if (!isAudioInput) {
      updateCoverPreview();
      return;
    }
    updateAudioPreview(input);
    if (isAudioInput && input.id === 'audio' && input.files.length > 1) {
      fillMultiTracks(input.files);
    }
  });
}

document.querySelectorAll('.upload-zone').forEach(bindDropZone);

releaseForm?.addEventListener('play', (event) => {
  if (!(event.target instanceof HTMLAudioElement)) return;
  releaseForm.querySelectorAll('.audio-upload-preview audio').forEach((audio) => {
    if (audio !== event.target) {
      audio.pause();
    }
  });
}, true);

multiAudioDrop?.addEventListener('dragenter', (event) => {
  event.preventDefault();
  multiAudioDrop.classList.add('is-dragging');
});

multiAudioDrop?.addEventListener('dragover', (event) => {
  event.preventDefault();
  multiAudioDrop.classList.add('is-dragging');
});

multiAudioDrop?.addEventListener('dragleave', (event) => {
  event.preventDefault();
  multiAudioDrop.classList.remove('is-dragging');
});

multiAudioDrop?.addEventListener('drop', (event) => {
  event.preventDefault();
  multiAudioDrop.classList.remove('is-dragging');
  fillMultiTracks(event.dataTransfer?.files || []);
});

releaseForm?.addEventListener('submit', (event) => {
  if (typeSelect.value === 'Single') {
    if ((!editingRelease || !existingSingleAudio) && !document.getElementById('audio')?.files?.length) {
      event.preventDefault();
      toast(uploadText('upload_music_choose_audio_toast'));
    }
    return;
  }

  const rows = Array.from(trackList.querySelectorAll('.frow'));
  if (rows.length > <?= (int)GREENERRY_MAX_RELEASE_TRACKS ?>) {
    event.preventDefault();
    toast(uploadLang() === 'en'
      ? 'A release can have at most <?= (int)GREENERRY_MAX_RELEASE_TRACKS ?> tracks.'
      : 'Um lançamento pode ter no máximo <?= (int)GREENERRY_MAX_RELEASE_TRACKS ?> faixas.');
    return;
  }
  const hasCompleteTrack = rows.some((row) => {
    const title = row.querySelector('input[type="text"]')?.value?.trim();
    const file = row.querySelector('input[type="file"]')?.files?.length;
    const trackId = Number(row.querySelector('input[name="track_id[]"]')?.value || 0);
    const currentAudio = existingTracks.find((track) => Number(track.id) === trackId)?.audio || '';
    return title && (file || (editingRelease && trackId > 0 && currentAudio));
  });

  if (!hasCompleteTrack) {
    event.preventDefault();
    toast(uploadText('upload_music_need_track_toast'));
  }
});

syncReleaseMode();
bindAudioPreview(singleAudioPreview);
if (editingRelease && typeSelect.value === 'Single' && existingTracks[0]?.audioUrl) {
  showExistingAudioPreview(singleAudioPreview, existingTracks[0]);
}
applyUploadMusicLanguage();
window.addEventListener('greenerry:langchange', applyUploadMusicLanguage);
</script>

<?php include '../includes/footer.php'; ?>
