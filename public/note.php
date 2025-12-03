<?php
/**
 * Produktivitätstool - Public Note View
 * View shared notes without login
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$token = $_GET['token'] ?? '';

if (empty($token)) {
    http_response_code(404);
    die('Notiz nicht gefunden');
}

// Get note by token
$note = dbFetchOne(
    "SELECT n.*, u.username as author_name
     FROM notes n
     JOIN users u ON n.user_id = u.id
     WHERE n.public_token = ? AND n.is_public = 1",
    [$token]
);

if (!$note) {
    http_response_code(404);
    die('Notiz nicht gefunden oder nicht mehr öffentlich');
}

// Decode content
$content = $note['content'];
$contentHtml = '';

// Try to parse as Quill Delta
$delta = json_decode($content, true);
if (json_last_error() === JSON_ERROR_NONE && isset($delta['ops'])) {
    // Convert Delta to HTML (basic conversion)
    $contentHtml = '';
    foreach ($delta['ops'] as $op) {
        if (is_string($op['insert'])) {
            $text = htmlspecialchars($op['insert']);
            $text = nl2br($text);
            
            $attrs = $op['attributes'] ?? [];
            
            if (!empty($attrs['bold'])) $text = "<strong>$text</strong>";
            if (!empty($attrs['italic'])) $text = "<em>$text</em>";
            if (!empty($attrs['underline'])) $text = "<u>$text</u>";
            if (!empty($attrs['strike'])) $text = "<s>$text</s>";
            if (!empty($attrs['code'])) $text = "<code>$text</code>";
            if (!empty($attrs['link'])) $text = "<a href=\"{$attrs['link']}\" target=\"_blank\" rel=\"noopener\">$text</a>";
            
            // Handle code blocks
            if (!empty($attrs['code-block'])) {
                $lang = is_string($attrs['code-block']) ? $attrs['code-block'] : '';
                $text = "<pre><code class=\"language-$lang\">$text</code></pre>";
            }
            
            // Handle headers
            if (!empty($attrs['header'])) {
                $level = (int)$attrs['header'];
                $text = "<h$level>$text</h$level>";
            }
            
            $contentHtml .= $text;
        }
    }
} elseif ($note['content_type'] === 'markdown') {
    // Markdown content - will be rendered client-side
    $contentHtml = '<div id="markdown-content" data-markdown="' . htmlspecialchars($content) . '"></div>';
} else {
    // Assume HTML content
    $contentHtml = $content;
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($note['title']); ?> - Geteilte Notiz</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github.min.css" rel="stylesheet">
    
    <style>
        :root {
            --bg-color: #ffffff;
            --bg-secondary: #f8f9fa;
            --text-color: #212529;
            --text-secondary: #6c757d;
            --border-color: #dee2e6;
            --primary: #3498db;
        }
        
        @media (prefers-color-scheme: dark) {
            :root {
                --bg-color: #1a1d21;
                --bg-secondary: #22262b;
                --text-color: #e9ecef;
                --text-secondary: #adb5bd;
                --border-color: #343a40;
            }
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg-color);
            color: var(--text-color);
            line-height: 1.6;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        header {
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }
        
        h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .meta {
            font-size: 0.875rem;
            color: var(--text-secondary);
        }
        
        .meta span {
            margin-right: 1rem;
        }
        
        .content {
            font-size: 1rem;
        }
        
        .content h1, .content h2, .content h3 {
            margin-top: 1.5rem;
            margin-bottom: 0.75rem;
        }
        
        .content p {
            margin-bottom: 1rem;
        }
        
        .content ul, .content ol {
            margin-bottom: 1rem;
            padding-left: 1.5rem;
        }
        
        .content blockquote {
            border-left: 4px solid var(--primary);
            padding-left: 1rem;
            margin: 1rem 0;
            color: var(--text-secondary);
        }
        
        .content pre {
            background: var(--bg-secondary);
            padding: 1rem;
            border-radius: 8px;
            overflow-x: auto;
            margin: 1rem 0;
        }
        
        .content code {
            background: var(--bg-secondary);
            padding: 0.2em 0.4em;
            border-radius: 4px;
            font-size: 0.9em;
        }
        
        .content pre code {
            background: none;
            padding: 0;
        }
        
        .content a {
            color: var(--primary);
            text-decoration: none;
        }
        
        .content a:hover {
            text-decoration: underline;
        }
        
        .content img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
        }
        
        footer {
            margin-top: 3rem;
            padding-top: 1rem;
            border-top: 1px solid var(--border-color);
            font-size: 0.875rem;
            color: var(--text-secondary);
            text-align: center;
        }
        
        footer a {
            color: var(--primary);
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1><?php echo htmlspecialchars($note['title']); ?></h1>
            <div class="meta">
                <span>Von <?php echo htmlspecialchars($note['author_name']); ?></span>
                <span>Aktualisiert: <?php echo date('d.m.Y H:i', strtotime($note['updated_at'])); ?></span>
            </div>
        </header>
        
        <article class="content">
            <?php echo $contentHtml; ?>
        </article>
        
        <footer>
            <p>Erstellt mit dem Produktivitätstool</p>
        </footer>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <script>
        // Highlight all code blocks
        hljs.highlightAll();
        
        // Render markdown if present
        const markdownEl = document.getElementById('markdown-content');
        if (markdownEl && typeof marked !== 'undefined') {
            const markdown = markdownEl.dataset.markdown;
            marked.setOptions({
                highlight: function(code, lang) {
                    if (lang && hljs.getLanguage(lang)) {
                        return hljs.highlight(code, { language: lang }).value;
                    }
                    return code;
                },
                breaks: true,
                gfm: true
            });
            markdownEl.innerHTML = marked.parse(markdown);
            
            // Re-highlight code blocks
            markdownEl.querySelectorAll('pre code').forEach((block) => {
                hljs.highlightElement(block);
            });
        }
    </script>
</body>
</html>
