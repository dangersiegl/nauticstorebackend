<!-- src/Views/product/create.php -->
<?php
$pageTitle = 'Neues Produkt anlegen';
require __DIR__ . '/../partials/header.php';
?>

<div class="content-box">
    <h2>Neues Produkt anlegen</h2>

    <?php if (!empty($error)): ?>
        <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="post" action="/product/store" class="admin-form">
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

<!-- CKEditor Initialisierung und Toggle-Funktion -->
<script>
let editor = null;
let isHTML = false;
const btnToggle = document.getElementById('toggle-html');
const textarea = document.getElementById('description');

function createEditor() {
    // Deaktiviere Button während Init
    btnToggle.disabled = true;
    return ClassicEditor
        .create(textarea, {
            toolbar: [ 'heading', '|', 'bold', 'italic', 'underline', 'link', 'bulletedList', 'numberedList', 'blockQuote' ],
            heading: {
                options: [
                    { model: 'paragraph', title: 'Paragraph', class: 'ck-heading_paragraph' },
                    { model: 'heading1', view: 'h1', title: 'Heading 1', class: 'ck-heading_heading1' },
                    { model: 'heading2', view: 'h2', title: 'Heading 2', class: 'ck-heading_heading2' },
                ]
            }
        })
        .then(newEditor => {
            editor = newEditor;
            btnToggle.disabled = false;
            return newEditor;
        })
        .catch(error => {
            console.error(error);
            btnToggle.disabled = false;
        });
}

function destroyEditor() {
    if (!editor) {
        return Promise.resolve();
    }
    btnToggle.disabled = true;
    return editor.destroy()
        .then(() => {
            editor = null;
            btnToggle.disabled = false;
        })
        .catch(err => {
            console.error('Fehler beim Zerstören des Editors:', err);
            btnToggle.disabled = false;
        });
}

// Initial erstellen
createEditor().then(() => {
    isHTML = false;
    btnToggle.textContent = 'HTML anzeigen';
});

// Toggle-Handler
btnToggle.addEventListener('click', async function () {
    // Falls bereits in Arbeit: nichts tun
    if (btnToggle.disabled) return;

    if (!isHTML) {
        // Wechsel zu HTML: Hol Daten, zerstöre Editor, zeige HTML
        if (!editor) return;
        try {
            btnToggle.disabled = true;
            const data = await editor.getData();
            // Setze den reinen HTML-Quelltext in das Textarea-Element
            textarea.value = data;
            await destroyEditor();
            isHTML = true;
            this.textContent = 'Texteditor anzeigen';
            // Optional: Höhe anpassen
            textarea.style.height = '200px';
        } catch (e) {
            console.error(e);
        } finally {
            btnToggle.disabled = false;
        }
    } else {
        // Wechsel zurück zur WYSIWYG-Ansicht: erstelle Editor neu und fülle Daten
        try {
            btnToggle.disabled = true;
            await createEditor();
            if (editor) {
                // Editor erstellt -> Daten setzen (falls vorhanden)
                editor.setData(textarea.value || '');
            }
            isHTML = false;
            this.textContent = 'HTML anzeigen';
        } catch (e) {
            console.error(e);
        } finally {
            btnToggle.disabled = false;
        }
    }
});
</script>

<?php 
require __DIR__ . '/../partials/footer.php';
?>
