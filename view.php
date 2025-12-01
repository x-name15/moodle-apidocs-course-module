<?php
/**
 * view archive.
 *
 * @package     mod_apidocs
 * @author      Felix Manrique / Mr Jacket
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

@ini_set('memory_limit', '512M');
@ini_set('max_execution_time', 300);

$id = required_param('id', PARAM_INT);
$action = optional_param('action', 'view', PARAM_ALPHA);

$cm = get_coursemodule_from_id('apidocs', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id'=>$cm->course], '*', MUST_EXIST);
$moduleinstance = $DB->get_record('apidocs', ['id'=>$cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/apidocs:view', $context);

$fs = get_file_storage();
$files = $fs->get_area_files($context->id, 'mod_apidocs', 'spec', 0, 'filename', false);
if (empty($files)) die("No file uploaded.");
$userfile = reset($files);
$filename = $userfile->get_filename();
$specContent = $userfile->get_content();

$isAsync = (strpos($specContent, 'asyncapi:') !== false) || (strpos(strtolower($filename), 'asyncapi') !== false);
$isMd = (substr(strtolower($filename), -3) === '.md');

function getLocalContent($filename) {
    global $CFG;
    $path = $CFG->dirroot . '/mod/apidocs/static/' . $filename;
    if (file_exists($path)) {
        $c = file_get_contents($path);
        return str_replace('</script>', '<\/script>', $c);
    }
    return "console.error('FALTA ARCHIVO LOCAL: $filename');";
}

$strPreview = get_string('btn_preview', 'mod_apidocs');
$strRaw     = get_string('btn_raw', 'mod_apidocs');
$strCopy    = get_string('btn_copy', 'mod_apidocs');
$strCopied  = get_string('msg_copied', 'mod_apidocs');

if ($action === 'player' && $isAsync) {
    while (ob_get_level()) ob_end_clean();

    $jsReact   = getLocalContent('react.js');
    $jsDom     = getLocalContent('react-dom.js');
    $jsEngine  = getLocalContent('asyncapi-standalone.js');
    
    $cssPath = $CFG->dirroot . '/mod/apidocs/static/asyncapi.css';
    $cssContent = file_exists($cssPath) ? file_get_contents($cssPath) : '';
    
    $safeSpec = json_encode($specContent);

    ?>
    <!DOCTYPE html>
    <html lang="<?php echo current_language(); ?>">
    <head>
        <meta charset="UTF-8">
        <title>AsyncAPI Viewer</title>
        <style>
            /* Reset */
            html, body { margin:0; padding:0; height:100vh; background:#fff; font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif; overflow:hidden; }
            
            /* --- TOOLBAR --- */
            #toolbar {
                height: 50px; background: #fdfdfd; border-bottom: 1px solid #e5e5e5;
                display: flex; align-items: center; justify-content: space-between;
                padding: 0 20px; box-sizing: border-box;
            }
            .btn-group { display: flex; gap: 10px; }
            button {
                background: #fff; border: 1px solid #dcdcdc; border-radius: 4px;
                padding: 6px 12px; font-size: 13px; font-weight: 600; color: #333;
                cursor: pointer; transition: all 0.2s;
            }
            button:hover { background: #f0f0f0; }
            button.active { background: #eef4ff; color: #1f75cb; border-color: #1f75cb; }

            /* --- CONTENIDO --- */
            #content-area { height: calc(100vh - 50px); overflow-y: auto; position: relative; }
            #root { width: 100%; min-height: 100%; }
            #raw-view { display: none; padding: 20px; background: #f6f8fa; min-height: 100%; box-sizing: border-box; }
            pre { margin: 0; }
            code { font-family: "SFMono-Regular", Consolas, monospace; font-size: 13px; white-space: pre-wrap; }
            
            #loader { position:fixed; top:55%; left:50%; transform:translate(-50%, -50%); color:#666; font-size:1.2em; font-weight:bold;}
            
            /* Estilos del Motor */
            <?php echo $cssContent; ?>
        </style>

        <script>
            window.process = { env: { NODE_ENV: 'production' } };
            window.require = function(mod) { 
                if (mod === 'react') return window.React;
                if (mod === 'react-dom') return window.ReactDOM;
                return {}; 
            };
            window.module = { exports: {} };
            window.exports = window.module.exports;
            window.define = undefined;
        </script>

        <script><?php echo $jsReact; ?></script>
        <script>
            if (!window.React && window.module.exports.createElement) window.React = window.module.exports;
            if (!window.ReactDOM && window.module.exports.createRoot) window.ReactDOM = window.module.exports;
        </script>
        <script><?php echo $jsDom; ?></script>
        <script><?php echo $jsEngine; ?></script>
    </head>
    <body>
        <div id="toolbar">
            <div class="btn-group">
                <button id="btn-render" class="active" onclick="switchMode('render')">
                    👁️ <?php echo $strPreview; ?>
                </button>
                <button id="btn-raw" onclick="switchMode('raw')">
                    💻 <?php echo $strRaw; ?>
                </button>
            </div>
            <div class="btn-group">
                <button id="btn-copy" onclick="copyCode()">
                    📋 <?php echo $strCopy; ?>
                </button>
            </div>
        </div>

        <div id="content-area">
            <div id="loader">Cargando motor...</div>
            <div id="root"></div>
            <div id="raw-view"><pre><code id="code-block"></code></pre></div>
        </div>

        <script>
            const specContent = <?php echo $safeSpec; ?>;
            const strCopied = "✅ <?php echo $strCopied; ?>";
            const strCopy = "📋 <?php echo $strCopy; ?>";

            function switchMode(mode) {
                const root = document.getElementById('root');
                const raw = document.getElementById('raw-view');
                const btnRender = document.getElementById('btn-render');
                const btnRaw = document.getElementById('btn-raw');

                if (mode === 'render') {
                    root.style.display = 'block'; raw.style.display = 'none';
                    btnRender.classList.add('active'); btnRaw.classList.remove('active');
                } else {
                    root.style.display = 'none'; raw.style.display = 'block';
                    btnRender.classList.remove('active'); btnRaw.classList.add('active');
                    if (!document.getElementById('code-block').innerText) {
                        document.getElementById('code-block').innerText = specContent;
                    }
                }
            }

            function copyCode() {
                navigator.clipboard.writeText(specContent).then(() => {
                    const btn = document.getElementById('btn-copy');
                    btn.innerText = strCopied;
                    setTimeout(() => btn.innerText = strCopy, 2000);
                });
            }

            window.onload = function() {
                const loader = document.getElementById('loader');
                const root = document.getElementById('root');
                try {
                    let Engine = window.AsyncApiStandalone || window.AsyncAPI || window.AsyncApiReact || window.module.exports.AsyncApiStandalone || window.module.exports.default || window.module.exports;
                    if (Engine && !Engine.render && Engine.default) Engine = Engine.default;

                    if (Engine && Engine.render) {
                        loader.style.display = 'none';
                        Engine.render({
                            schema: specContent,
                            config: { show: { errors: true, sidebar: true, info: true } }
                        }, root);
                    } else {
                        throw new Error("Motor no encontrado.");
                    }
                } catch (e) {
                    loader.innerHTML = '<h3 style="color:red">Error: ' + e.message + '</h3>';
                }
            };
        </script>
    </body>
    </html>
    <?php
    exit;
}

$PAGE->set_url('/mod/apidocs/view.php', ['id'=>$id]);
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_pagelayout('incourse');

echo $OUTPUT->header();

echo "<style>
    /* Ocultar elementos de Moodle */
    .activity-completion, [data-region='completion-info'], .completion-info, button[data-action='toggle-manual-completion'], .automatic-completion-conditions { display: none !important; }
    .activity-description, .mod_introbox { display: none !important; }

    #doc-wrapper { width: 100%; max-width: 100%; margin-top: 15px; background: #fff; border: 1px solid #d0d7de; border-radius: 4px; overflow: hidden; }
    
    /* Toolbar Local (OpenAPI/MD) */
    .local-toolbar {
        background: #fdfdfd; border-bottom: 1px solid #e5e5e5; padding: 10px 20px;
        display: flex; justify-content: space-between; align-items: center;
    }
    .local-btn {
        background: #fff; border: 1px solid #dcdcdc; border-radius: 4px; padding: 6px 12px;
        font-size: 13px; font-weight: 600; cursor: pointer; color: #333; margin-right: 5px;
    }
    .local-btn:hover { background: #f0f0f0; }
    .local-btn.active { background: #eef4ff; color: #1f75cb; border-color: #1f75cb; }

    #viewer-root { min-height: 600px; }
    #raw-content { display: none; padding: 20px; background: #f6f8fa; overflow: auto; max-height: 800px; }
    #raw-content pre { margin: 0; font-family: monospace; white-space: pre-wrap; font-size: 13px; }

    #doc-frame { width: 100%; height: 100vh; min-height: 850px; border: none; display: block; }
    @media (min-width: 768px) { #region-main, .region-main-content { width: 100% !important; max-width: 100% !important; padding: 0 !important; } }
</style>";

if ($isAsync) {
    $playerUrl = new moodle_url('/mod/apidocs/view.php', ['id' => $id, 'action' => 'player']);
    echo '<div id="doc-wrapper"><iframe id="doc-frame" src="' . $playerUrl->out(false) . '"></iframe></div>';

} else {
    $mdCss = $CFG->dirroot . '/mod/apidocs/static/markdown.css';
    $swCss = $CFG->dirroot . '/mod/apidocs/static/swagger.css';
    
    echo "<style>";
    if ($isMd && file_exists($mdCss)) echo file_get_contents($mdCss);
    if (!$isMd && file_exists($swCss)) echo file_get_contents($swCss);
    echo "</style>";

    echo "<script>" . getLocalContent('js-yaml.js') . "</script>"; 
    if ($isMd) {
        echo "<script>" . getLocalContent('marked.js') . "</script>";
    } else {
        echo "<script>" . getLocalContent('swagger.js') . "</script>";
    }

    echo '<div id="doc-wrapper">';

    echo '<div class="local-toolbar">
            <div>
                <button id="btn-main-render" class="local-btn active" onclick="toggleMainView(\'render\')">👁️ '.$strPreview.'</button>
                <button id="btn-main-raw" class="local-btn" onclick="toggleMainView(\'raw\')">💻 '.$strRaw.'</button>
            </div>
            <div>
                <button id="btn-main-copy" class="local-btn" onclick="copyMainCode()">📋 '.$strCopy.'</button>
            </div>
          </div>';

    echo '<div id="viewer-root"></div>';
    echo '<div id="raw-content"><pre>' . htmlspecialchars($specContent) . '</pre></div>';
    echo '</div>'; 

    $safeContent = json_encode($specContent);
    $isMdBool = $isMd ? 'true' : 'false';

    echo "<script>
        const strCopiedMain = '✅ $strCopied';
        const strCopyMain = '📋 $strCopy';

        function toggleMainView(mode) {
            const viewer = document.getElementById('viewer-root');
            const raw = document.getElementById('raw-content');
            const btnRender = document.getElementById('btn-main-render');
            const btnRaw = document.getElementById('btn-main-raw');

            if(mode === 'render') {
                viewer.style.display = 'block'; raw.style.display = 'none';
                btnRender.classList.add('active'); btnRaw.classList.remove('active');
            } else {
                viewer.style.display = 'none'; raw.style.display = 'block';
                btnRender.classList.remove('active'); btnRaw.classList.add('active');
            }
        }
        function copyMainCode() {
            const content = $safeContent;
            navigator.clipboard.writeText(content).then(() => {
                const btn = document.getElementById('btn-main-copy');
                btn.innerText = strCopiedMain;
                setTimeout(() => btn.innerText = strCopyMain, 2000);
            });
        }
    </script>";

    echo "<script>
    (async function(){
        const root = document.getElementById('viewer-root');
        const content = $safeContent;
        if ($isMdBool) {
            root.className = 'markdown-body';
            root.style.padding = '40px';
            root.innerHTML = marked.parse(content);
        } else {
            let specObj = null;
            try { specObj = jsyaml.load(content); } catch(e) { specObj = null; }
            SwaggerUIBundle({
                spec: specObj,
                dom_id: '#viewer-root',
                presets: [SwaggerUIBundle.presets.apis],
                layout: 'BaseLayout'
            });
        }
    })();
    </script>";
}

echo "<script>document.addEventListener('DOMContentLoaded', function() {
    const r = document.getElementById('region-main');
    if(r) { r.style.maxWidth = '100%'; r.classList.remove('container'); }
});</script>";

echo $OUTPUT->footer();
?>
