<!-- src/Views/product/create.php -->
<?php
$pageTitle = 'Neues Produkt anlegen';
require __DIR__ . '/../partials/header.php';
?>

<div class="admin-main">
    <div class="login-box">
        <h2>Neues Produkt anlegen</h2>

        <?php if (!empty($error)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- action="/product/store" vorausgesetzt, du hast in index.php die Route 'product/store' => ['controller'=>'product','action'=>'store'] -->
        <form method="post" action="/product/store" class="login-form">
            <div class="form-group">
                <label for="name">Name:</label>
                <input type="text" name="name" id="name" required>
            </div>

            <div class="form-group">
                <label for="description">Beschreibung:</label>
                <textarea name="description" id="description" rows="6"></textarea>
                <!-- Umschaltbutton für Text/HTML -->
                <button type="button" id="toggle-html" class="btn btn-secondary">HTML anzeigen</button>
            </div>

            <div class="form-group">
                <label for="price">Preis (EUR):</label>
                <input type="number" step="0.01" name="price" id="price" required>
            </div>

            <button type="submit" class="btn btn-primary">Speichern</button>
        </form>
    </div>
</div>

<!-- CKEditor Initialisierung und Toggle-Funktion -->
<script>
let editor;
let isHTML = false;

ClassicEditor
    .create( document.querySelector( '#description' ), {
        toolbar: [ 'heading', '|', 'bold', 'italic', 'underline', 'link', 'bulletedList', 'numberedList', 'blockQuote' ],
        heading: {
            options: [
                { model: 'paragraph', title: 'Paragraph', class: 'ck-heading_paragraph' },
                { model: 'heading1', view: 'h1', title: 'Heading 1', class: 'ck-heading_heading1' },
                { model: 'heading2', view: 'h2', title: 'Heading 2', class: 'ck-heading_heading2' },
                // Weitere Überschriftenoptionen nach Bedarf
            ]
        }
    } )
    .then( newEditor => {
        editor = newEditor;
    } )
    .catch( error => {
        console.error( error );
    } );

document.getElementById('toggle-html').addEventListener('click', function() {
    if (!editor) {
        return;
    }
    if (!isHTML) {
        // Wechsel zu HTML-Ansicht
        document.getElementById('description').value = editor.getData();
        editor.destroy()
            .then(() => {
                isHTML = true;
                this.textContent = 'WYSIWYG anzeigen';
                // Optional: Passe die Höhe des Textbereichs an
                document.getElementById('description').style.height = '200px';
            })
            .catch(error => {
                console.error(error);
            });
    } else {
        // Wechsel zurück zur WYSIWYG-Ansicht
        editor = ClassicEditor
            .create( document.querySelector( '#description' ), {
                toolbar: [ 'heading', '|', 'bold', 'italic', 'underline', 'link', 'bulletedList', 'numberedList', 'blockQuote' ],
                heading: {
                    options: [
                        { model: 'paragraph', title: 'Paragraph', class: 'ck-heading_paragraph' },
                        { model: 'heading1', view: 'h1', title: 'Heading 1', class: 'ck-heading_heading1' },
                        { model: 'heading2', view: 'h2', title: 'Heading 2', class: 'ck-heading_heading2' },
                        // Weitere Überschriftenoptionen nach Bedarf
                    ]
                }
            } )
            .then( newEditor => {
                newEditor.setData( document.getElementById('description').value );
                isHTML = false;
                this.textContent = 'HTML anzeigen';
            } )
            .catch( error => {
                console.error( error );
            });
    }
});
</script>

<?php 
require __DIR__ . '/../partials/footer.php';
?>
