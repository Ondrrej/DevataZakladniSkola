<?php 
// připojení k databázi
require_once 'db_connect.php'; 

// získání hledaného výrazu z GET parametru
$q = isset($_GET['q']) ? $_GET['q'] : ""; 
?>
<!DOCTYPE html>
<html lang="cs">
<head>
  <meta charset="UTF-8">
  <title>Vyhledávání</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
  <div class="navbar">
    <div class="logo-placeholder">
      <img src="logo.png" alt="Logo">
    </div>
    
    <form class="search-form desktop" method="GET" action="search.php">
      <input type="text" name="q" placeholder="Vyhledat..." value="<?php echo htmlspecialchars($q, ENT_QUOTES); ?>" required>
      <button type="submit">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
          <path d="M11.742 10.344a6.5 6.5 0 10-1.397 1.398h-.001l3.85 3.85a1 1 0 001.415-1.414l-3.85-3.85zm-5.242 1.656a5 5 0 110-10 5 5 0 010 10z"/>
        </svg>
      </button>
    </form>
    
    <a href="index.html" class="nav-links">← Zpět na hlavní stránku</a>
  </div>

  <main>
    <div class="post-container">
      <?php
      if (!$q) {
        echo "<h1>Zadejte hledaný výraz</h1>";
        echo "</div></main></body></html>";
        $conn->close();
        exit;
      }

      // vyhledávání v databázy
      $sql = "SELECT id, nazev AS title, SUBSTRING(obsah, 1, 100) AS excerpt 
              FROM blog_posts 
              WHERE (nazev LIKE ? OR obsah LIKE ?)
              ORDER BY id DESC";
      $stmt = $conn->prepare($sql);
      if (!$stmt) {
        die("Chyba při přípravě dotazu: " . $conn->error);
      }
      $likeQ = "%" . $q . "%";
      $stmt->bind_param("ss", $likeQ, $likeQ);
      $stmt->execute();
      $result = $stmt->get_result();

      echo "<h1>Výsledky hledání na blogu pro: <em>" . htmlspecialchars($q, ENT_QUOTES) . "</em></h1>";

      if ($result->num_rows > 0) {
        echo "<div class='posts-grid'>";
        while ($row = $result->fetch_assoc()) {
          ?>
          <div class="post">
            <h2 class="post-title"><?php echo htmlspecialchars($row['title'], ENT_QUOTES); ?></h2>
            <div class="post-excerpt"><?php echo htmlspecialchars($row['excerpt'], ENT_QUOTES); ?>...</div>
            <div class="read-more">
              <!-- Pro blogové příspěvky se načítá celý článek  -->
              <a href="#" data-id="<?php echo $row['id']; ?>">Číst celý příspěvek</a>
            </div>
          </div>
          <?php
        }
        echo "</div>";
      } else {
        echo "<div class='no-results'>Nebyly nalezeny žádné výsledky v blogových příspěvcích.</div>";
      }
      $stmt->close();
      $conn->close();
      ?>
    </div>

    <!-- vyhledávání ve statických stránkách -->
    <div class="static-results">
      <h1>Výsledky hledání ve stránkách pro: <em><?php echo htmlspecialchars($q, ENT_QUOTES); ?></em></h1>
      <?php
         // seznam statických stránek
         $staticPages = array(
             array("file" => "rozvrhy.html", "title" => "Rozvrhy ke stažení"),
             array("file" => "historie.html", "title" => "Historie"),
             array("file" => "kontakty.html", "title" => "Kontakty"),
             array("file" => "skolni_druzina.html", "title" => "Skolní družina"),
             array("file" => "dokumenty.html", "title" => "Dokumenty"),
             array("file" => "el_zakovska_knizka.html", "title" => "Elektronická žákovská knížka"),
             array("file" => "obsazeni_uceben.html", "title" => "Obsazení učeben"),
             array("file" => "skolni_email.html", "title" => "Školní email"),
             array("file" => "skolni_zamestnanci.html", "title" => "Školní zamestnanci"),
             array("file" => "soucasnost.html", "title" => "Současnost"),
             array("file" => "specialni_pedagogo.html", "title" => "Specialní pedagog"),
             );

         $foundStatic = false;
         foreach ($staticPages as $page) {
             if (file_exists($page["file"])) {
                 // načtení obsahu a odstranění HTML tagů
                 $content = file_get_contents($page["file"]);
                 $plainText = strip_tags($content);
                 // pokud se hledaný výraz v textu objeví
                 if (stripos($plainText, $q) !== false) {
                     $foundStatic = true;
                     $pos = stripos($plainText, $q);
                     $start = ($pos > 50) ? $pos - 50 : 0;
                     $excerpt = substr($plainText, $start, 150);
                     
                     echo '<div class="post">';
                     echo '<h2 class="post-title"><a href="' . $page["file"] . '">' . htmlspecialchars($page["title"], ENT_QUOTES) . '</a></h2>';
                     echo '<div class="post-excerpt">... ' . htmlspecialchars($excerpt, ENT_QUOTES) . ' ...</div>';
                     echo '</div>';
                 }
             }
         }
         if (!$foundStatic) {
             echo "<div class='no-results'>Nebyly nalezeny žádné výsledky ve statických stránkách.</div>";
         }
      ?>
    </div>
  </main>

  <footer>
    <p>© 2025 9. ZŠ Zlín</p>
    <p>
        <a href="mailto:info@9zszlin.cz">info@9zszlin.cz</a> | Tel: +420 577 210 772<br>
        <a href="admin.php">Administrace</a>
    </p>
  </footer>

  <div id="myModal" class="modal">
    <div class="modal-content">
      <span class="modal-close">&times;</span>
      <div id="modal-body"></div>
    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const modal = document.getElementById('myModal');
      const modalClose = document.querySelector('.modal-close');
      const modalBody = document.getElementById('modal-body');

      modalClose.onclick = function() {
        modal.style.display = "none";
        modalBody.innerHTML = "";
      };

      window.onclick = function(event) {
        if (event.target == modal) {
          modal.style.display = "none";
          modalBody.innerHTML = "";
        }
      };

      // načítání celého příspěvku pro záznamy z databáze
      const readMoreLinks = document.querySelectorAll('.read-more a');
      readMoreLinks.forEach(link => {
        link.addEventListener('click', function(e) {
          e.preventDefault();
          const postId = this.getAttribute('data-id');
          fetch('get_post.php?id=' + postId)
            .then(response => response.text())
            .then(data => {
              modalBody.innerHTML = data;
              modal.style.display = "block";
            })
            .catch(err => {
              modalBody.innerHTML = "<p>Nepodařilo se načíst příspěvek.</p>";
              modal.style.display = "block";
              console.error("Chyba při načítání příspěvku:", err);
            });
        });
      });
    });
  </script>
</body>
</html>