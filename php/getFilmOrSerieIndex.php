<?php if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
  header("location: baldflix_login.php");
  exit;
}

function getFilmsOrSeriesIndex($link)
{
  $validFields = ['title'];
  $validDirections = ['ASC', 'DESC'];
  $orderBy = "title ASC";
  if (isset($_GET['tri'])) {
    list($field, $direction) = explode('_', $_GET['tri'] . '_');
    $direction = strtoupper($direction);

    if (in_array($field, $validFields) && in_array($direction, $validDirections)) {
      $orderBy = "$field $direction";
    }
  }

  $sql = "SELECT
            film.film_ID,
            film.film_image_path,
            film.film_synopsis,
            film.film_duree,
            film.film_tags,
            film.film_path,
            film.film_miniature_path,
            film.film_title AS title,
            NULL AS serie_ID,
            NULL AS serie_title,
            NULL AS serie_tags,
            NULL AS serie_synopsis,
            NULL AS serie_image_path,
            'film' AS type
          FROM
            film
          GROUP BY
            film.film_ID
          UNION ALL
          SELECT
            NULL AS film_ID,
            NULL AS film_image_path,
            NULL AS film_synopsis,
            NULL AS film_duree,
            NULL AS film_tags,
            NULL AS film_path,
            NULL AS film_miniature_path,
            NULL AS film_title,
            serie.serie_ID, 
            serie.serie_title AS title,
            serie.serie_tags,
            serie.serie_synopsis,
            serie.serie_image_path,
            'serie' AS serie_type
          FROM
            serie
          GROUP BY
            serie.serie_ID
          ORDER BY $orderBy";

  if ($stmt = mysqli_prepare($link, $sql)) {
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $filmsOrSeries = array();
    if ($result && $result->num_rows > 0) {
      while ($row = $result->fetch_assoc()) {
        array_push($filmsOrSeries, $row);
      }
    }
    mysqli_stmt_close($stmt);
    return $filmsOrSeries;
  } else {
    echo "Erreur de préparation de la requête.";
    return array();
  }
}

$filmsOrSeries = getFilmsOrSeriesIndex($link);

echo '<div class="container container_cat" id="' . "index" . 'Container">';
echo '<div class="cat_select">';
echo '<h3 id="' . "index" . '">' . "Film & Serie" . '</h3>';
echo '<form id="triForm" method="get">';
echo '<input type="radio" id="title_asc" name="tri" value="title_asc" onchange="submitForm()" ' . (isset($_GET['tri']) && $_GET['tri'] == 'title_asc' ? 'checked' : '') . '>';
echo '<label for="title_asc">Titre (A-Z)</label>';
echo '<input type="radio" id="title_desc" name="tri" value="title_desc" onchange="submitForm()" ' . (isset($_GET['tri']) && $_GET['tri'] == 'title_desc' ? 'checked' : '') . '>';
echo '<label for="title_desc">Titre (Z-A)</label>';
echo '</form>';
echo '</div>';
echo '<script>
function submitForm() {
  document.getElementById("triForm").submit();
}
</script>';
echo '<div class="box box_cat box_' . "index" . '">';

foreach ($filmsOrSeries as $item) {
  $id = htmlspecialchars($item['type'] === 'film' ? $item['film_ID'] : $item['serie_ID']);
  $type = htmlspecialchars($item['type']); // Ajout du type (film ou serie)
  $title = htmlspecialchars_decode($item['type'] === 'film' ? $item['title'] : $item['serie_title']);
  $title = str_replace("_", " ", $title);
  $image_path = htmlspecialchars($item['type'] === 'film' ? $item['film_image_path'] : $item['serie_image_path']);
  $synopsis = htmlspecialchars_decode($item['type'] === 'film' ? $item['film_synopsis'] : $item['serie_synopsis']);
  $duree = htmlspecialchars($item['type'] === 'film' ? $item['film_duree'] : ''); // Durée pour les séries non disponible ici
  $video_path = htmlspecialchars($item['type'] === 'film' ? $item['film_path'] : ''); // Chemin vidéo pour les séries non disponible ici
  $miniature = htmlspecialchars($item['type'] === 'film' ? $item['film_miniature_path'] : ''); // Miniature pour les séries non disponible ici

  echo '<div class="box_div">
          <a href="javascript:void(0);" onclick="openModal(this)"
             data-id="' . $id . '"
             data-type="' . $type . '"
             data-image="' . $image_path . '"
             data-title="' . $title . '"
             data-synopsis="' . $synopsis . '"
             data-duration="' . $duree . '"
             data-video="' . $video_path . '"
             data-miniature="' . $miniature . '">
              <img src="' . $image_path . '" alt="' . $title . '">
          </a>
      </div>';
}
