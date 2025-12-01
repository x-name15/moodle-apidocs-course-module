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
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>AsyncAPI v3 Viewer</title>
        <style>
            html, body { margin:0; padding:0; height:100vh; background:#fff; font-family:sans-serif; overflow:hidden; }
            #root { height:100%; width:100%; overflow-y:auto; }
            #loader { 
                position:fixed; top:50%; left:50%; transform:translate(-50%, -50%); 
                color:#555; font-size:14px; font-weight:bold;
            }
            <?php echo $cssContent; ?>
        </style>

        <script>
            window.process = { env: { NODE_ENV: 'production' } };
            
            // Require falso para conectar React
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
        <div id="loader">Cargando motor v3...</div>
        <div id="root"></div>
        <script>
            window.onload = function() {
                const loader = document.getElementById('loader');
                const root = document.getElementById('root');
                const spec = <?php echo $safeSpec; ?>;
                try {
                    let Engine = window.AsyncApiStandalone || 
                                 window.AsyncAPI || 
                                 window.AsyncApiReact || 
                                 window.module.exports.AsyncApiStandalone ||
                                 window.module.exports.default ||
                                 window.module.exports;

                    if (Engine && !Engine.render && Engine.default) Engine = Engine.default;
                    if (Engine && Engine.render) {
                        loader.style.display = 'none';
                        Engine.render({
                            schema: spec,
                            config: { show: { errors: true, sidebar: true, info: true } }
                        }, root);
                    } else {
                        throw new Error("Librerías cargadas (v2.6.5), pero el motor no arrancó.");
                    }
                } catch (e) {
                    loader.innerHTML = '<div style="color:red; padding:20px; border:1px solid red;">' +
                        '<h3>Error Fatal</h3><p>' + e.message + '</p></div>';
                    console.error("Detalles:", e);
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
    /* Ocultar botón 'Marcar como hecho' */
    .activity-completion, 
    [data-region='completion-info'],
    .completion-info,
    button[data-action='toggle-manual-completion'],
    .automatic-completion-conditions { 
        display: none !important; 
    }

    /* Ocultar descripción de Moodle si se cuela */
    .activity-description, .mod_introbox { display: none !important; }

    /* Contenedor Limpio */
    #doc-wrapper { width: 100%; max-width: 100%; margin-top: 15px; background: #fff; border: 1px solid #d0d7de; }
    #doc-frame { width: 100%; height: 100vh; min-height: 850px; border: none; display: block; }
    
    /* Ancho completo */
    @media (min-width: 768px) { 
        #region-main, .region-main-content { width: 100% !important; max-width: 100% !important; padding: 0 !important; } 
    }
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

    // 2. Inyectar Scripts Locales
    echo "<script>" . getLocalContent('js-yaml.js') . "</script>"; 
    if ($isMd) {
        echo "<script>" . getLocalContent('marked.js') . "</script>";
    } else {
        echo "<script>" . getLocalContent('swagger.js') . "</script>";
    }

    echo '<div id="doc-wrapper" style="padding:20px;"><div id="viewer-root"></div></div>';
    
    $safeContent = json_encode($specContent);
    $isMdBool = $isMd ? 'true' : 'false';

    echo "<script>
    (async function(){
        const root = document.getElementById('viewer-root');
        const content = $safeContent;
        if ($isMdBool) {
            root.className = 'markdown-body';
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