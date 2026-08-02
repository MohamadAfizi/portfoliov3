<?php
declare(strict_types=1);

// ============================================
// Portfolio V3 CMS (JSON-backed)
// URL should map to this file at /cms
// ============================================

// Simple gate (MVP): set CMS_PASSWORD in env or default placeholder.
$expectedPassword = getenv('CMS_PASSWORD') ?: '123';

session_start();
if (!isset($_SESSION['cms_authed'])) {
    $_SESSION['cms_authed'] = false;
}

function jsonResponse(array $payload, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function cmsDataPath(): string {
    return __DIR__ . '/data/content.json';
}

function loadCmsData(): array {
    $path = cmsDataPath();
    if (!file_exists($path)) {
        return [
            'profile_summary' => '',
            'tech_stack' => [],
            'projects' => [],
            'milestones' => [],
            'industry_experiences' => [],
        ];
    }

    $raw = file_get_contents($path);
    if ($raw === false) {
        return [
            'profile_summary' => '',
            'tech_stack' => [],
            'projects' => [],
            'milestones' => [],
            'industry_experiences' => [],
        ];
    }

    $data = json_decode($raw, true);
    if (!is_array($data)) {
        return [
            'profile_summary' => '',
            'tech_stack' => [],
            'projects' => [],
            'milestones' => [],
            'industry_experiences' => [],
        ];
    }

    if (!isset($data['profile_summary']) || !is_string($data['profile_summary'])) {
        $data['profile_summary'] = '';
    }

    if (!isset($data['tech_stack']) || !is_array($data['tech_stack'])) {
        $data['tech_stack'] = [];
    }

    foreach (['projects', 'milestones', 'industry_experiences'] as $key) {
        if (!isset($data[$key]) || !is_array($data[$key])) {
            $data[$key] = [];
        }
    }

    return $data;
}

function atomicWriteJson(string $path, array $data): void {
    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }

    $tmp = $path . '.' . uniqid('tmp_', true) . '.json';
    $encoded = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($encoded === false) {
        throw new RuntimeException('Failed to encode JSON');
    }

    $fp = fopen($tmp, 'wb');
    if ($fp === false) {
        throw new RuntimeException('Failed to create temp file');
    }
    fwrite($fp, $encoded);
    fflush($fp);
    fclose($fp);

    // Best-effort atomic replace
    rename($tmp, $path);
}

function requireAuthed(string $expectedPassword): void {
    if (!($_SESSION['cms_authed'] ?? false)) {
        // Allow login POST
        if (isset($_POST['action']) && $_POST['action'] === 'login') {
            $pass = (string)($_POST['password'] ?? '');
            if (hash_equals($expectedPassword, $pass)) {
                $_SESSION['cms_authed'] = true;
                header('Location: ' . strtok($_SERVER['REQUEST_URI'], "?#"));
                exit;
            }
        }

        // Show login page
        http_response_code(401);
        $err = isset($_POST['action']) && $_POST['action'] === 'login' ? 'Invalid password' : '';
        echo '<!doctype html><html><head><meta charset="utf-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
            <title>CMS Login</title>
            <style>
              body{margin:0;background:#000;color:#ddd;font-family:Inter,Segoe UI,Arial;}
              .wrap{max-width:520px;margin:10vh auto;padding:20px;background:#111;border:1px solid #333;border-radius:12px;}
              h1{margin:0 0 12px;color:#6defF8;font-size:18px;}
              input,button{width:100%;padding:12px;border-radius:8px;border:1px solid #333;background:#000;color:#ddd;margin-top:10px;}
              button{background:rgba(109,239,248,0.12);border-color:rgba(109,239,248,0.35);cursor:pointer;}
              body{margin:0;background:#f3f4f6;color:#1f2937;font-family:Inter,Segoe UI,Arial;}
              .wrap{max-width:520px;margin:10vh auto;padding:20px;background:#fff;border:1px solid #e5e7eb;border-radius:12px;shadow:0 4px 6px -1px rgb(0 0 0 / 0.1);}
              h1{margin:0 0 12px;color:#0891b2;font-size:18px;}
              input,button{width:100%;padding:12px;border-radius:8px;border:1px solid #d1d5db;background:#fff;color:#1f2937;margin-top:10px;}
              button{background:#0891b2;color:#fff;border:none;font-weight:600;cursor:pointer;}
              .err{color:#f87171;margin-top:10px;}
            </style></head><body>
            <div class="wrap">
              <h1>Portfolio CMS</h1>
              <form method="post">
                <input type="hidden" name="action" value="login"/>
                <input type="password" name="password" placeholder="CMS Password" autofocus />
                <button type="submit">Login</button>
              </form>
              ' . ($err ? '<div class="err">' . htmlspecialchars($err, ENT_QUOTES, 'UTF-8') . '</div>' : '') . '
            </div>
          </body></html>';
        exit;
    }
}

requireAuthed($expectedPassword);

// Handle CRUD actions via POST (JSON file)
if (isset($_POST['action'])) {
    $action = (string)$_POST['action'];

    $data = loadCmsData();
    $allowedSections = ['projects', 'milestones', 'industry_experiences'];
    $section = isset($_POST['section']) ? (string)$_POST['section'] : '';

    if ($action === 'save_profile_summary') {
        $profileSummary = trim((string)($_POST['profileSummary'] ?? ''));
        $data['profile_summary'] = $profileSummary;
        atomicWriteJson(cmsDataPath(), $data);
        jsonResponse(['ok' => true]);
    }

    if ($action === 'save_tech_stack') {
        $raw = (string)($_POST['techStack'] ?? '');
        $parts = preg_split('/[,\r\n]+/', $raw);

        $normalized = [];
        $seen = [];

        foreach ($parts as $p) {
            $s = trim((string)$p);
            if ($s === '') continue;

            $key = mb_strtolower($s, 'UTF-8');
            if (isset($seen[$key])) continue;

            $seen[$key] = true;
            $normalized[] = $s;
        }

        usort($normalized, function($a, $b) {
            return strcasecmp((string)$a, (string)$b);
        });

        $data['tech_stack'] = $normalized;
        atomicWriteJson(cmsDataPath(), $data);

        jsonResponse(['ok' => true, 'saved' => $data['tech_stack']]);
    }

    if (!in_array($action, ['list', 'logout', 'get', 'reset', 'save_tech_stack', 'save_profile_summary'], true) && !in_array($section, $allowedSections, true)) {
        jsonResponse(['ok' => false, 'error' => 'Invalid section'], 400);
    }

    if ($action === 'list') {
        $out = $data;
        // Provide stable index positions
        jsonResponse(['ok' => true, 'data' => $out]);
    }

    if ($action === 'create') {
        $title = trim((string)($_POST['title'] ?? ''));
        $description = (string)($_POST['description'] ?? '');
        $group = trim((string)($_POST['group'] ?? ''));
        $techStackRaw = (string)($_POST['techStack'] ?? '');

        if ($title === '') jsonResponse(['ok' => false, 'error' => 'Title required'], 422);

        $techStack = [];
        if ($techStackRaw !== '') {
            // Accept comma-separated or JSON array string
            if (str_starts_with(trim($techStackRaw), '[')) {
                $tmp = json_decode($techStackRaw, true);
                if (is_array($tmp)) {
                    $techStack = array_values(array_filter(array_map('strval', $tmp)));
                }
            } else {
                $parts = array_map('trim', explode(',', $techStackRaw));
                $techStack = array_values(array_filter($parts, fn($v) => $v !== ''));
            }
        }

        $item = [
            'title' => $title,
            'description' => $description,
            'group' => $group,
            'techStack' => $techStack,
        ];

        $data[$section][] = $item;
        atomicWriteJson(cmsDataPath(), $data);
        jsonResponse(['ok' => true]);
    }

    if ($action === 'update') {
        $idx = (int)($_POST['index'] ?? -1);
        $title = trim((string)($_POST['title'] ?? ''));
        $description = (string)($_POST['description'] ?? '');
        $group = trim((string)($_POST['group'] ?? ''));
        $techStackRaw = (string)($_POST['techStack'] ?? '');

        if ($title === '') jsonResponse(['ok' => false, 'error' => 'Title required'], 422);
        if (!isset($data[$section][$idx])) jsonResponse(['ok' => false, 'error' => 'Index not found'], 404);

        $techStack = [];
        if ($techStackRaw !== '') {
            if (str_starts_with(trim($techStackRaw), '[')) {
                $tmp = json_decode($techStackRaw, true);
                if (is_array($tmp)) {
                    $techStack = array_values(array_filter(array_map('strval', $tmp)));
                }
            } else {
                $parts = array_map('trim', explode(',', $techStackRaw));
                $techStack = array_values(array_filter($parts, fn($v) => $v !== ''));
            }
        }

        $data[$section][$idx] = [
            'title' => $title,
            'description' => $description,
            'group' => $group,
            'techStack' => $techStack,
        ];

        atomicWriteJson(cmsDataPath(), $data);
        jsonResponse(['ok' => true]);
    }

    if ($action === 'delete') {
        $idx = (int)($_POST['index'] ?? -1);
        if (!isset($data[$section][$idx])) jsonResponse(['ok' => false, 'error' => 'Index not found'], 404);
        array_splice($data[$section], $idx, 1);
        atomicWriteJson(cmsDataPath(), $data);
        jsonResponse(['ok' => true]);
    }

    if ($action === 'logout') {
        $_SESSION['cms_authed'] = false;
        header('Location: index.php');
        exit;
    }

    jsonResponse(['ok' => false, 'error' => 'Unknown action'], 400);
}

// Render CMS UI
$sections = [
    'projects' => 'Projects',
    'milestones' => 'Milestones',
    'industry_experiences' => 'Industry Experiences',
];

$groupsProjects = [
    'Independent Projects',
    'Technical Experiments',
    'Guided Projects',
    'Academic Projects',
];

$current = loadCmsData();

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Portfolio CMS</title>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    body{margin:0;background:#f9fafb;color:#1f2937;font-family:Inter,Segoe UI,Arial;}
    .top{display:flex;gap:12px;justify-content:space-between;align-items:center;padding:16px 20px;border-bottom:1px solid #e5e7eb;position:sticky;top:0;background:#fff;z-index:5;}
    .brand{color:#0891b2;font-weight:800;letter-spacing:.3px;}
    .btn{padding:10px 14px;border-radius:10px;border:1px solid #0891b2;background:#0891b2;color:#fff;cursor:pointer;font-weight:500;}
    .btn.secondary{background:#fff;color:#4b5563;border-color:#d1d5db;}
    .wrap{max-width:1200px;margin:0 auto;padding:18px;}
    .panel{background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:14px;box-shadow:0 1px 3px 0 rgb(0 0 0 / 0.1);}

    .grid{display:grid;grid-template-columns:260px 1fr;gap:14px;}
    .tabs{display:flex;flex-direction:column;gap:8px;}
    .tab{padding:10px 12px;border-radius:10px;border:1px solid #222;background:#060606;color:#aaa;cursor:pointer;}
    .tab.active{border-color:rgba(109,239,248,0.45);color:#6defF8;background:rgba(109,239,248,0.08);}
    .tab{padding:10px 12px;border-radius:10px;border:1px solid #e5e7eb;background:#f9fafb;color:#6b7280;cursor:pointer;text-align:left;}
    .tab.active{border-color:#0891b2;color:#0891b2;background:#ecfeff;}

    table{width:100%;border-collapse:collapse;}
    th,td{padding:10px 8px;border-bottom:1px solid #222;vertical-align:top;}
    th{color:#999;font-weight:600;font-size:12px;letter-spacing:.4px;text-transform:uppercase;}
    td .muted{color:#999;font-size:12px;}
    th,td{padding:12px 8px;border-bottom:1px solid #f3f4f6;vertical-align:top;}
    th{color:#6b7280;font-weight:600;font-size:11px;letter-spacing:.4px;text-transform:uppercase;text-align:left;}
    td .muted{color:#6b7280;font-size:12px;}
    .row-actions{display:flex;gap:8px;flex-wrap:wrap;}
    .mini{padding:7px 10px;border-radius:10px;border:1px solid #333;background:#111;color:#ddd;cursor:pointer;font-size:12px;}
    .mini.danger{border-color:rgba(248,113,113,0.5);color:#f87171;}
    .mini{padding:6px 10px;border-radius:8px;border:1px solid #d1d5db;background:#fff;color:#374151;cursor:pointer;font-size:12px;}
    .mini.danger{border-color:#fee2e2;color:#ef4444;background:#fef2f2;}

    .form{display:grid;grid-template-columns:1fr 1fr;gap:10px;align-items:start;}
    .form .full{grid-column:1/-1;}
    label{font-size:12px;color:#999;display:block;margin-bottom:6px;}
    input,textarea,select{width:100%;padding:10px 12px;border-radius:10px;border:1px solid #333;background:#000;color:#ddd;}
    label{font-size:12px;color:#4b5563;display:block;margin-bottom:6px;font-weight:600;}
    input,textarea,select{width:100%;padding:10px 12px;border-radius:10px;border:1px solid #d1d5db;background:#fff;color:#1f2937;}
    textarea{min-height:90px;resize:vertical;}

    .help{font-size:12px;color:#999;margin-top:8px;line-height:1.4;}
    .help{font-size:12px;color:#6b7280;margin-top:8px;line-height:1.4;}

    /* Modal editor */
    .modalOverlay{
      position:fixed; inset:0; background:rgba(15,23,42,0.55);
      display:none; align-items:center; justify-content:center;
      padding:18px; z-index:1000;
    }
    .modalOverlay.open{display:flex;}
    .modal{
      width:100%; max-width:860px;
      background:#fff; border-radius:14px;
      border:1px solid #e5e7eb;
      box-shadow:0 20px 60px rgba(0,0,0,0.25);
      overflow:hidden;
    }
    .modalHeader{
      display:flex; align-items:center; justify-content:space-between;
      padding:14px 16px; border-bottom:1px solid #e5e7eb;
      background:#fff;
    }
    .modalHeader h3{margin:0; font-size:16px; color:#0891b2;}
    .modalClose{
      width:34px; height:34px; border-radius:10px;
      border:1px solid #d1d5db; background:#fff; cursor:pointer;
      display:flex; align-items:center; justify-content:center;
      color:#374151; font-size:18px; line-height:1;
    }
    .modalBody{padding:14px 16px;}
    .modalFooter{display:flex; gap:10px; flex-wrap:wrap; justify-content:flex-end; padding:0 16px 16px;}
    @media (max-width: 900px){
      .grid{grid-template-columns:1fr;}
      .tabs{flex-direction:row;overflow:auto;}
      .form{grid-template-columns:1fr;}
    }
  </style>
</head>
<body>
  <div class="top">
    <div class="brand">Portfolio V3 CMS</div>
    <div style="display:flex;gap:10px;align-items:center;">
      <form id="logoutForm" method="post" style="margin:0;">
        <input type="hidden" name="action" value="logout"/>
        <button class="btn secondary" type="submit">Logout</button>
      </form>
    </div>
  </div>

  <div class="wrap">
    <div class="grid">
      <div class="panel tabs" id="tabs">
        <?php foreach($sections as $key => $label): ?>
          <button type="button" class="tab" data-section="<?php echo h($key); ?>"><?php echo h($label); ?></button>
        <?php endforeach; ?>
      </div>

      <div class="panel" id="mainPanel">
        <h2 style="margin:0 0 12px;color:#111;" id="sectionTitle">Manage</h2>
        <div class="help" id="sectionHint"></div>


        <div style="margin:14px 0 18px;">
          <h3 style="margin:0 0 10px; font-size:14px; color:#0891b2;">Profile Summary</h3>
          <label style="margin:0 0 6px;">Text shown on the homepage under the name</label>
          <textarea id="profileSummaryInput" placeholder="Edit your profile summary..." style="min-height:120px;"></textarea>
          <div style="display:flex; gap:10px; margin-top:12px; align-items:center;">
            <button class="btn" type="button" id="saveProfileSummaryBtn">Save Summary</button>
          </div>
        </div>

        <div style="margin:14px 0 0;">
          <h3 style="margin:0 0 10px; font-size:14px; color:#0891b2;">Tech Stack</h3>
          <label style="margin:0 0 6px;">Add skill tags shown on the homepage (click x to remove)</label>

          <div style="display:flex; gap:10px; margin-top:8px; align-items:center;">
            <input
              id="techStackAddInput"
              type="text"
              placeholder="Type a tech and press Enter (or type comma/line)…"
              style="flex:1; min-height:44px;"
            />
            <button class="btn" type="button" id="techStackAddBtn">Add</button>
          </div>

          <div
            id="techStackPills"
            style="display:flex; gap:8px; flex-wrap:wrap; margin-top:12px;"
            aria-label="Tech stack pills"
          ></div>

          <div style="display:flex; gap:10px; margin-top:14px; align-items:center;">
            <button class="btn" type="button" id="saveTechStackBtn">Save Tech Stack</button>
          </div>
        </div>

        <div id="contentArea">
          <!-- list -->
          <div id="listArea"></div>

          <hr style="border:none;border-top:1px solid #222;margin:16px 0;"/>
          <hr style="border:none;border-top:1px solid #e5e7eb;margin:24px 0;"/>

          <div class="help" style="margin:12px 0 0;">
            Click <b>Edit</b> on any row to open the editor modal.
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Editor modal -->
  <div class="modalOverlay" id="editorModalOverlay" aria-hidden="true">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
      <div class="modalHeader">
        <h3 id="modalTitle">Add / Edit entry</h3>
        <button class="modalClose" id="modalCloseBtn" type="button" aria-label="Close">×</button>
      </div>
      <div class="modalBody">
        <form id="entryForm" onsubmit="return false;">
          <input type="hidden" name="section" id="formSection" value="projects"/>
          <input type="hidden" name="mode" id="formMode" value="create"/>
          <input type="hidden" name="editIndex" id="editIndex" value="-1"/>

          <div class="form">
            <div class="full">
              <label>Title</label>
              <input id="f_title" name="title" type="text" placeholder="Card title" required/>
            </div>

            <div>
              <label>Group</label>
              <select id="f_group" name="group"></select>
              <div class="help" id="groupHelp"></div>
            </div>

            <div>
              <label>Tech Stack (comma-separated)</label>
              <input id="f_tech" name="techStack" type="text" placeholder="PHP, SQLite"/>
            </div>

            <div class="full">
              <label>Description (shows in modal)</label>
              <textarea id="f_desc" name="description" placeholder="Long description..."></textarea>
            </div>
          </div>
        </form>
      </div>
      <div class="modalFooter">
        <button class="btn" type="button" id="saveBtn">Save</button>
        <button class="btn secondary" type="button" id="cancelBtn">Cancel</button>
      </div>
    </div>
  </div>

<script>
(function(){
  const groupsProjects = <?php echo json_encode($groupsProjects, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); ?>;

  const tabs = Array.from(document.querySelectorAll('.tab'));
  const listArea = document.getElementById('listArea');
  const sectionTitle = document.getElementById('sectionTitle');
  const sectionHint = document.getElementById('sectionHint');

  const formSection = document.getElementById('formSection');
  const formMode = document.getElementById('formMode');
  const editIndex = document.getElementById('editIndex');

  const fTitle = document.getElementById('f_title');
  const fGroup = document.getElementById('f_group');
  const groupHelp = document.getElementById('groupHelp');
  const fTech = document.getElementById('f_tech');
  const fDesc = document.getElementById('f_desc');

  const saveBtn = document.getElementById('saveBtn');
  const cancelBtn = document.getElementById('cancelBtn');

  const profileSummaryInput = document.getElementById('profileSummaryInput');
  const saveProfileSummaryBtn = document.getElementById('saveProfileSummaryBtn');

  const techStackAddInput = document.getElementById('techStackAddInput');
  const techStackAddBtn = document.getElementById('techStackAddBtn');
  const techStackPills = document.getElementById('techStackPills');
  const saveTechStackBtn = document.getElementById('saveTechStackBtn');

  function csrflessPost(payload){
    return fetch(window.location.pathname, {
      method: 'POST',
      headers: {'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'},
      body: new URLSearchParams(payload)
    }).then(r => r.json());
  }

  let cached = null;

  function loadAll(){
    return csrflessPost({action:'list'}).then(res => {
      cached = res.data || cached;

      // Prefill profile summary editor
      if(profileSummaryInput && cached && typeof cached.profile_summary !== 'undefined'){
        profileSummaryInput.value = cached.profile_summary || '';
      }

      if(techStackPills && cached && Array.isArray(cached.tech_stack)){
        const existing = cached.tech_stack
          .filter(v => typeof v === 'string' && v.trim() !== '')
          .map(v => v.trim());
        renderTechStackPills(existing);
      }

      return cached;
    });
  }

  function escapeHtml(s){
    return String(s)
      .replaceAll('&','&amp;')
      .replaceAll('<','<')
      .replaceAll('>','>')
      .replaceAll('"','"')
      .replaceAll("'",'&#039;');
  }

  function groupOptionsFor(section){
    if(section === 'projects') return groupsProjects;
    return [];
  }

  function setGroupInput(section){
    const opts = groupOptionsFor(section);
    fGroup.innerHTML = '';

    if(opts.length){
      groupHelp.textContent = 'Choose which homepage tab it belongs to.';
      opts.forEach(g => {
        const o = document.createElement('option');
        o.value = g; o.textContent = g;
        fGroup.appendChild(o);
      });
    } else {
      groupHelp.textContent = 'Group is free text for these sections (use the select value as display).';
      const o = document.createElement('option');
      o.value = '';
      o.textContent = '—';
      fGroup.appendChild(o);
    }
  }

  function renderList(section){
    const data = (cached && cached[section]) ? cached[section] : [];
    const rows = data.map((item, idx) => {
      const tech = Array.isArray(item.techStack) ? item.techStack.join(', ') : '';
      const group = item.group || '';
      const title = item.title || '';
      const desc = (item.description || '').slice(0, 160);
      const descMuted = (item.description || '').length > 160 ? '...' : '';
      return `
        <tr>
          <td style="width:40px;">${idx}</td>
          <td>
            <div style="font-weight:700;color:#111;">${escapeHtml(title)}</div>
            <div class="muted">Group: ${escapeHtml(group)}</div>
            <div class="muted">Tech: ${escapeHtml(tech)}</div>
            <div class="muted" style="margin-top:6px;">${escapeHtml(desc)}${descMuted}</div>
          </td>
          <td style="width:250px;">
            <div class="row-actions">
              <button type="button" class="mini" onclick="window.__cmsEdit(${idx}, '${section}')">Edit</button>
              <button type="button" class="mini danger" onclick="window.__cmsDelete(${idx}, '${section}')">Delete</button>
            </div>
          </td>
        </tr>
      `;
    }).join('');

    if(rows === ''){
      listArea.innerHTML = `<div class="muted" style="padding:10px 0;color:#999;">No entries yet.</div>`;
      return;
    }

    listArea.innerHTML = `
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Entry</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>${rows}</tbody>
      </table>
    `;
  }

  const editorModalOverlay = document.getElementById('editorModalOverlay');
  const modalCloseBtn = document.getElementById('modalCloseBtn');
  const modalTitle = document.getElementById('modalTitle');

  function openModal(){
    editorModalOverlay.classList.add('open');
    editorModalOverlay.setAttribute('aria-hidden', 'false');
    setTimeout(() => { try{ fTitle.focus(); }catch(e){} }, 0);
  }

  function clearForm(){
    fTitle.value = '';
    fDesc.value = '';
    fTech.value = '';
    setGroupInput(formSection.value);
    if(formSection.value === 'projects') fGroup.value = groupsProjects[0] || '';
    editIndex.value = '-1';
    modalTitle.textContent = 'Add / Edit entry';
    formMode.value = 'create';
  }

  function closeModal(){
    editorModalOverlay.classList.remove('open');
    editorModalOverlay.setAttribute('aria-hidden', 'true');
    clearForm();
  }

  editorModalOverlay.addEventListener('click', (e) => {
    if(e.target === editorModalOverlay) closeModal();
  });
  modalCloseBtn.addEventListener('click', () => closeModal());
  document.addEventListener('keydown', (e) => {
    if(e.key === 'Escape' && editorModalOverlay.classList.contains('open')) closeModal();
  });

  function setFormFor(section, mode, item){
    formSection.value = section;
    formMode.value = mode;
    editIndex.value = (mode === 'edit') ? String(item.index) : '-1';

    fTitle.value = item.title || '';
    fDesc.value = item.description || '';

    if(section === 'projects'){
      setGroupInput(section);
      fGroup.value = item.group || (groupsProjects[0] || '');
    } else {
      setGroupInput(section);
      fGroup.value = item.group || '';
    }

    fTech.value = Array.isArray(item.techStack) ? item.techStack.join(', ') : '';

    modalTitle.textContent = (mode === 'edit') ? 'Edit Entry' : 'Add Entry';
    openModal();
  }

  window.__cmsEdit = function(idx, section){
    const item = cached[section][idx];
    setFormFor(section, 'edit', { index: idx, ...item });
  };

  function toastSuccess(title, text){
    Swal.fire({ icon:'success', title, text, timer: 2000, showConfirmButton:false });
  }
  function toastError(title, text){
    Swal.fire({ icon:'error', title, text });
  }

  window.__cmsDelete = async function(idx, section){
    const confirmRes = await Swal.fire({
      icon: 'warning',
      title: 'Delete this entry?',
      text: 'This action cannot be undone.',
      showCancelButton: true,
      confirmButtonText: 'Delete',
      cancelButtonText: 'Cancel'
    });
    if(!confirmRes.isConfirmed) return;

    const res = await csrflessPost({action:'delete', section, index: idx});
    if(res.ok){
      await loadAll();
      renderList(section);
      if(formSection.value === section) closeModal();
      toastSuccess('Deleted', 'Entry removed successfully.');
    } else {
      toastError('Delete failed', res.error || 'Unknown error');
    }
  };

  const logoutForm = document.getElementById('logoutForm');
  if(logoutForm){
    logoutForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const confirmRes = await Swal.fire({
        icon: 'warning',
        title: 'Logout?',
        showCancelButton: true,
        confirmButtonText: 'Logout',
        cancelButtonText: 'Cancel'
      });
      if(confirmRes.isConfirmed) logoutForm.submit();
    });
  }

  if(saveProfileSummaryBtn && profileSummaryInput){
    saveProfileSummaryBtn.addEventListener('click', async function(){
      const profileSummary = String(profileSummaryInput.value || '');

      const res = await csrflessPost({
        action: 'save_profile_summary',
        profileSummary
      });

      if(res.ok){
        await loadAll();
        Swal.fire({ icon:'success', title:'Saved', text:'Profile summary updated.' , timer: 2000, showConfirmButton:false });
      } else {
        Swal.fire({ icon:'error', title:'Save failed', text: res.error || 'Unknown error' });
      }
    });
  }

  function dedupeAndSortTechStack(values){
    const seen = new Set();
    const out = [];
    (values || []).forEach(v => {
      const s = String(v).trim();
      if(!s) return;
      if(seen.has(s.toLowerCase())) return;
      seen.add(s.toLowerCase());
      out.push(s);
    });
    out.sort((a,b) => a.localeCompare(b, undefined, { sensitivity: 'base' }));
    return out;
  }

  function renderTechStackPills(values){
    if(!techStackPills) return;
    const next = dedupeAndSortTechStack(values);
    techStackPills.innerHTML = '';

    next.forEach((skill) => {
      const pill = document.createElement('div');
      pill.style.display = 'inline-flex';
      pill.style.alignItems = 'center';
      pill.style.gap = '8px';
      pill.style.padding = '8px 10px';
      pill.style.borderRadius = '999px';
      pill.style.border = '1px solid #d1d5db';
      pill.style.background = '#ecfeff';
      pill.style.color = '#0891b2';
      pill.style.fontWeight = '600';
      pill.style.fontSize = '12px';

      const label = document.createElement('span');
      label.textContent = skill;

      const xBtn = document.createElement('button');
      xBtn.type = 'button';
      xBtn.textContent = 'x';
      xBtn.setAttribute('aria-label', `Remove ${skill}`);
      xBtn.style.border = 'none';
      xBtn.style.background = 'transparent';
      xBtn.style.cursor = 'pointer';
      xBtn.style.color = '#0891b2';
      xBtn.style.fontSize = '14px';
      xBtn.style.lineHeight = '1';

      xBtn.addEventListener('click', () => {
        pill.remove();
      });

      pill.appendChild(label);
      pill.appendChild(xBtn);
      techStackPills.appendChild(pill);
    });
  }

  function getPillsValues(){
    if(!techStackPills) return [];
    return Array.from(techStackPills.querySelectorAll('div'))
      .map(p => p.querySelector('span')?.textContent)
      .filter(Boolean);
  }

  function addFromInputRaw(raw){
    if(!raw || !techStackPills) return;
    const parts = String(raw).split(/[,\\r\\n]+/).map(s => s.trim()).filter(Boolean);

    const current = getPillsValues();
    renderTechStackPills(current.concat(parts));
  }

  if(techStackAddBtn && techStackAddInput){
    techStackAddBtn.addEventListener('click', () => {
      const raw = techStackAddInput.value || '';
      addFromInputRaw(raw);
      techStackAddInput.value = '';
      try{ techStackAddInput.focus(); }catch(e){}
    });

    techStackAddInput.addEventListener('keydown', (e) => {
      if(e.key === 'Enter'){
        e.preventDefault();
        const raw = techStackAddInput.value || '';
        addFromInputRaw(raw);
        techStackAddInput.value = '';
      }
    });
  }

  if(saveTechStackBtn){
    saveTechStackBtn.addEventListener('click', async function(){
      const values = dedupeAndSortTechStack(getPillsValues());
      const payloadTechStack = values.join(',');

      const res = await csrflessPost({
        action: 'save_tech_stack',
        techStack: payloadTechStack
      });

      if(res.ok){
        await loadAll();
        Swal.fire({ icon:'success', title:'Saved', text:'Tech stack updated.' , timer: 2000, showConfirmButton:false });
      } else {
        Swal.fire({ icon:'error', title:'Save failed', text: res.error || 'Unknown error' });
      }
    });
  }

  saveBtn.addEventListener('click', async function(){
    const section = formSection.value;
    const mode = formMode.value;
    const title = fTitle.value.trim();
    const description = fDesc.value;
    const group = fGroup.value || '';
    const techStack = fTech.value;

    if(title === ''){
      Swal.fire({ icon:'info', title:'Title required', text:'Please provide a title.' });
      return;
    }

    const payload = {
      action: mode === 'edit' ? 'update' : 'create',
      section,
      index: editIndex.value,
      title,
      description,
      group,
      techStack
    };

    const res = await csrflessPost(payload);
    if(res.ok){
      await loadAll();
      renderList(section);
      closeModal();
      toastSuccess('Saved', 'Changes applied successfully.');
    } else {
      toastError('Save failed', res.error || 'Unknown error');
    }
  });

  cancelBtn.addEventListener('click', function(){
    closeModal();
  });

  function setActive(section){
    tabs.forEach(t => t.classList.toggle('active', t.dataset.section === section));
    formSection.value = section;

    const titleMap = {
      projects: 'Manage Projects',
      milestones: 'Manage Milestones',
      industry_experiences: 'Manage Industry Experiences'
    };
    sectionTitle.textContent = titleMap[section] || 'Manage';
    sectionHint.textContent = section === 'projects'
      ? 'Edit cards shown on the homepage under Projects tabs.'
      : 'Edit cards shown on the homepage under the corresponding category.';

    setGroupInput(section);
    clearForm();
    renderList(section);
  }

  tabs.forEach(t => t.addEventListener('click', function(){
    setActive(this.dataset.section);
  }));

  (async function(){
    await loadAll();
    const first = tabs[0] && tabs[0].dataset.section;
    if(first) setActive(first);
  })();
})();
</script>
</body>
</html>
