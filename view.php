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
if (empty($files)) {
    echo $OUTPUT->header();
    echo $OUTPUT->notification('No spec file found.');
    echo $OUTPUT->footer();
    exit;
}
$userfile = reset($files);
$filename = $userfile->get_filename();
$specContent = $userfile->get_content();

$isAsync = (strpos($specContent, 'asyncapi:') !== false) || (strpos(strtolower($filename), 'asyncapi') !== false);
$isMd = (substr(strtolower($filename), -3) === '.md');
$isOpenApi = !$isAsync && !$isMd; // Si no es Async ni Markdown, es OpenAPI

function getLocalContent($filename) {
    global $CFG;
    $path = $CFG->dirroot . '/mod/apidocs/static/' . $filename;
    if (file_exists($path)) {
        $c = file_get_contents($path);
        return str_replace('</script>', '<\/script>', $c);
    }
    return "console.warn('MISSING LOCAL FILE: $filename');";
}

function get_safe_string($identifier, $component, $default) {
    if (get_string_manager()->string_exists($identifier, $component)) {
        return get_string($identifier, $component);
    }
    return $default;
}

$strPreview = get_safe_string('btn_preview', 'mod_apidocs', 'Preview');
$strRaw     = get_safe_string('btn_raw', 'mod_apidocs', 'Source');
$strCopy    = get_safe_string('btn_copy', 'mod_apidocs', 'Copy');
$strCopied  = get_safe_string('msg_copied', 'mod_apidocs', 'Copied!');
$strSearch  = get_safe_string('search_placeholder', 'mod_apidocs', 'Search...');
$strToRedoc = get_safe_string('btn_switch_redoc', 'mod_apidocs', 'Use Redoc');
$strToSwag  = get_safe_string('btn_switch_swagger', 'mod_apidocs', 'Use Swagger');

if ($action === 'player' && $isAsync) {
    while (ob_get_level()) ob_end_clean();

    $jsReact   = getLocalContent('react.js');
    $jsDom     = getLocalContent('react-dom.js');
    $jsEngine  = getLocalContent('asyncapi-standalone.js');
    $cssPath   = $CFG->dirroot . '/mod/apidocs/static/asyncapi.css';
    $cssContent = file_exists($cssPath) ? file_get_contents($cssPath) : '';
    
    $safeSpec = json_encode($specContent);

    ?>
    <!DOCTYPE html>
    <html lang="<?php echo current_language(); ?>">
    <head>
        <meta charset="UTF-8">
        <title>AsyncAPI Viewer</title>
        <style>
            html, body { margin:0; padding:0; height:100vh; background:#fff; font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif; overflow:hidden; }
            #root { height:100%; width:100%; overflow-y:auto; }
            #loader { position:fixed; top:50%; left:50%; transform:translate(-50%, -50%); color:#666; font-size:1.2em; font-weight:bold;}
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
        <div id="loader">Loading Engine...</div>
        <div id="root"></div>
        <script>
            window.onload = function() {
                const loader = document.getElementById('loader');
                const root = document.getElementById('root');
                const spec = <?php echo $safeSpec; ?>;
                try {
                    let Engine = window.AsyncApiStandalone || window.AsyncAPI || window.AsyncApiReact || window.module.exports.AsyncApiStandalone || window.module.exports.default || window.module.exports;
                    if (Engine && !Engine.render && Engine.default) Engine = Engine.default;

                    if (Engine && Engine.render) {
                        loader.style.display = 'none';
                        Engine.render({
                            schema: spec,
                            config: { show: { errors: true, sidebar: true, info: true } }
                        }, root);
                    } else {
                        throw new Error("AsyncAPI Engine not found.");
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
    /* Ocultar elementos Moodle */
    .activity-completion, [data-region='completion-info'], .completion-info, button[data-action='toggle-manual-completion'], .automatic-completion-conditions { display: none !important; }
    .activity-description, .mod_introbox { display: none !important; }

    #doc-wrapper { width: 100%; max-width: 100%; margin-top: 15px; background: #fff; border: 1px solid #d0d7de; border-radius: 4px; overflow: hidden; display: flex; flex-direction: column; height: 85vh; min-height: 600px; }
    
    /* TOOLBAR COMPACTA (SLIM) */
    .local-toolbar {
        background: #fdfdfd; border-bottom: 1px solid #e5e5e5; padding: 0 10px; height: 40px; /* Altura reducida */
        display: flex; justify-content: space-between; align-items: center; box-sizing: border-box;
        transition: background 0.3s, border 0.3s;
    }
    .local-btn {
        background: #fff; border: 1px solid #dcdcdc; border-radius: 4px; 
        padding: 2px 10px; /* Padding reducido */
        font-family: -apple-system,BlinkMacSystemFont,'Segoe UI',Helvetica,Arial,sans-serif !important; 
        font-size: 12px !important; /* Letra pequeña */
        line-height: 22px !important; 
        font-weight: 500; cursor: pointer; color: #24292e; margin-right: 5px;
        display: inline-flex; align-items: center; justify-content: center; height: 26px; /* Altura botón */
        transition: all 0.2s;
    }
    .local-btn:hover { background: #f3f4f6; border-color: #1b1f2426; }
    .local-btn.active { background: #0969da; color: #fff; border-color: #0969da; }
    
    /* Search Box Compact */
    .search-box { position: relative; display: inline-flex; align-items: center; margin-right: 10px; }
    #main-search { 
        padding: 2px 8px; border: 1px solid #d0d7de; border-radius: 4px; width: 180px; 
        font-size: 12px !important; height: 26px; outline: none;
    }
    
    #content-container { flex: 1; overflow: hidden; position: relative; background: #fff; }
    
    /* Viewers */
    #viewer-root { height: 100%; overflow-y: auto; transition: filter 0.3s; }
    #redoc-root { height: 100%; overflow-y: auto; display: none; background: #fff; transition: filter 0.3s; }
    #doc-frame { width: 100%; height: 100%; border: none; display: block; transition: filter 0.3s; }

    /* RAW VIEW */
    #raw-content { 
        display: none; padding: 15px; background: #f6f8fa; height: 100%; overflow: auto; box-sizing: border-box; 
        color: #24292e; 
    }
    #raw-content pre { margin: 0; font-family: 'SFMono-Regular', Consolas, monospace; white-space: pre-wrap; font-size: 12px; }

    /* --- DARK MODE --- */
    .doc-dark-mode .local-toolbar { background: #161b22; border-bottom: 1px solid #30363d; }
    .doc-dark-mode #doc-wrapper { border-color: #30363d; background: #0d1117; }
    .doc-dark-mode .local-btn { background: #21262d; border-color: #363b42; color: #c9d1d9; }
    .doc-dark-mode .local-btn:hover { background: #30363d; border-color: #8b949e; }
    .doc-dark-mode .local-btn.active { background: #1f6feb; border-color: #1f6feb; color: #f0f6fc; }
    .doc-dark-mode #main-search { background: #0d1117; border-color: #30363d; color: #c9d1d9; }
    
    /* Contenido Dark */
    .doc-dark-mode #raw-content { background: #0d1117 !important; color: #c9d1d9 !important; }
    .doc-dark-mode #viewer-root, .doc-dark-mode #redoc-root, .doc-dark-mode #doc-frame { 
        filter: invert(0.92) hue-rotate(180deg); 
    }
    .doc-dark-mode img { filter: invert(1) hue-rotate(180deg); }

    @media (min-width: 768px) { #region-main, .region-main-content { width: 100% !important; max-width: 100% !important; padding: 0 !important; } }
</style>";

echo '<div id="doc-wrapper">';

// --- TOOLBAR ---
echo '<div class="local-toolbar">
        <div style="display:flex; align-items:center;">
            <button id="btn-main-render" class="local-btn active" onclick="toggleMainView(\'render\')">👁️ '.$strPreview.'</button>
            <button id="btn-main-raw" class="local-btn" onclick="toggleMainView(\'raw\')">💻 '.$strRaw.'</button>';

if ($isOpenApi) {
    echo '<button id="btn-engine-switch" class="local-btn" onclick="toggleEngine()" style="margin-left:10px; border-color:#0969da; color:#0969da;">🔄 '.$strToRedoc.'</button>';
}

echo '      <div class="search-box">
                <input type="text" id="main-search" placeholder="'.$strSearch.'" onkeypress="handleSearchKey(event)">
            </div>
        </div>
        <div style="display:flex; align-items:center;">
            <button id="btn-main-theme" class="local-btn" onclick="toggleMainTheme()">🌙</button>
            <button id="btn-main-copy" class="local-btn" onclick="copyMainCode()">📋 '.$strCopy.'</button>
        </div>
      </div>';

echo '<div id="content-container">';

if ($isAsync) {
    $playerUrl = new moodle_url('/mod/apidocs/view.php', ['id' => $id, 'action' => 'player']);
    echo '<iframe id="doc-frame" src="' . $playerUrl->out(false) . '"></iframe>';
} else {
    // Assets OpenAPI/MD
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
        echo "<script>" . getLocalContent('redoc.js') . "</script>";
    }

    echo '<div id="viewer-root"></div>';
    if ($isOpenApi) echo '<div id="redoc-root"></div>';
}

echo '<div id="raw-content"><pre>' . htmlspecialchars($specContent) . '</pre></div>';
echo '</div></div>'; 

$safeContent = json_encode($specContent);
$isMdBool = $isMd ? 'true' : 'false';
$isAsyncBool = $isAsync ? 'true' : 'false';
$isOpenApiBool = $isOpenApi ? 'true' : 'false';
$strCopiedJs = $strCopied; 
$strCopyJs = $strCopy;
$strToRedocJs = $strToRedoc;
$strToSwagJs = $strToSwag;

echo "<script>
    const isAsync = $isAsyncBool;
    const isOpenApi = $isOpenApiBool;
    const strCopied = '$strCopiedJs';
    const strCopy = '$strCopyJs';
    const strToRedoc = '$strToRedocJs';
    const strToSwag = '$strToSwagJs';
    const specContent = $safeContent;
    
    let currentEngine = 'swagger';
    let redocInitialized = false;

    function toggleMainView(mode) {
        const viewer = document.getElementById('viewer-root');
        const redoc = document.getElementById('redoc-root');
        const raw = document.getElementById('raw-content');
        const iframe = document.getElementById('doc-frame');
        
        const btnRender = document.getElementById('btn-main-render');
        const btnRaw = document.getElementById('btn-main-raw');
        const btnSwitch = document.getElementById('btn-engine-switch');

        if(mode === 'render') {
            if(isAsync) {
                iframe.style.display = 'block';
            } else {
                if(currentEngine === 'swagger') {
                    viewer.style.display = 'block';
                    if(redoc) redoc.style.display = 'none';
                } else {
                    viewer.style.display = 'none';
                    if(redoc) redoc.style.display = 'block';
                }
            }
            raw.style.display = 'none';
            btnRender.classList.add('active'); btnRaw.classList.remove('active');
            if(btnSwitch) btnSwitch.style.display = 'inline-flex';
        } else {
            if(isAsync) iframe.style.display = 'none';
            else {
                viewer.style.display = 'none';
                if(redoc) redoc.style.display = 'none';
            }
            raw.style.display = 'block';
            btnRender.classList.remove('active'); btnRaw.classList.add('active');
            if(btnSwitch) btnSwitch.style.display = 'none';
        }
    }

    function toggleEngine() {
        if (!isOpenApi) return;
        const viewer = document.getElementById('viewer-root');
        const redoc = document.getElementById('redoc-root');
        const btn = document.getElementById('btn-engine-switch');

        if (currentEngine === 'swagger') {
            viewer.style.display = 'none';
            redoc.style.display = 'block';
            btn.innerText = '🔄 ' + strToSwag;
            currentEngine = 'redoc';

            if (!redocInitialized) {
                if (typeof Redoc !== 'undefined') {
                    let specObj = null;
                    try { specObj = jsyaml.load(specContent); } catch(e) {}
                    Redoc.init(specObj || specContent, {
                        scrollYOffset: 50,
                        disableSearch: true
                    }, document.getElementById('redoc-root'));
                    redocInitialized = true;
                } else {
                    alert('Redoc library not found.');
                }
            }
        } else {
            redoc.style.display = 'none';
            viewer.style.display = 'block';
            btn.innerText = '🔄 ' + strToRedoc;
            currentEngine = 'swagger';
        }
    }

    function copyMainCode() {
        navigator.clipboard.writeText(specContent).then(() => {
            const btn = document.getElementById('btn-main-copy');
            const icon = btn.innerText.split(' ')[0] || '📋'; 
            btn.innerText = '✅ ' + strCopied;
            setTimeout(() => btn.innerText = icon + ' ' + strCopy, 2000);
        });
    }

    function toggleMainTheme() {
        const wrapper = document.getElementById('doc-wrapper');
        const btn = document.getElementById('btn-main-theme');
        wrapper.classList.toggle('doc-dark-mode');
        btn.innerText = wrapper.classList.contains('doc-dark-mode') ? '☀️' : '🌙';
    }

    function handleSearchKey(e) {
        if(e.key === 'Enter') {
            e.preventDefault(); 
            performSearch();
        }
    }

    function performSearch() {
        const term = document.getElementById('main-search').value;
        if (!term) return;

        if (isAsync) {
            const iframe = document.getElementById('doc-frame');
            if (iframe && iframe.contentWindow) {
                iframe.contentWindow.focus(); 
                iframe.contentWindow.find(term, false, false, true);
            }
        } else {
            window.find(term, false, false, true);
        }
    }
</script>";

if (!$isAsync) {
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