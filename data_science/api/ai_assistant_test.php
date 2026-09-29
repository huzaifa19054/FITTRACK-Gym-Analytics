<?php
require_once __DIR__ . "/../../config/auth.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>FITTRACK — AI Assistant Test</title>
<style>
    *{box-sizing:border-box}
    body{
        margin:0;
        font-family:Inter,Arial,sans-serif;
        background:#f5f7f8;
        color:#17212b;
        min-height:100vh;
        display:flex;
        align-items:center;
        justify-content:center;
        padding:24px;
    }
    .card{
        width:min(760px,100%);
        background:#fff;
        border:1px solid #e5e9ec;
        border-radius:18px;
        box-shadow:0 12px 35px rgba(0,0,0,.07);
        padding:28px;
    }
    h1{margin:0 0 8px;font-size:25px}
    .sub{color:#6b7785;margin-bottom:24px}
    textarea{
        width:100%;
        min-height:130px;
        resize:vertical;
        border:1px solid #d8dee4;
        border-radius:12px;
        padding:14px;
        font:inherit;
        outline:none;
    }
    textarea:focus{border-color:#16c7a4}
    button{
        margin-top:12px;
        border:0;
        border-radius:10px;
        padding:12px 18px;
        background:#16c7a4;
        color:white;
        font-weight:700;
        cursor:pointer;
    }
    button:disabled{opacity:.6;cursor:not-allowed}
    .result{
        margin-top:22px;
        background:#f7f9fa;
        border-radius:12px;
        padding:18px;
        white-space:pre-wrap;
        line-height:1.6;
        min-height:70px;
    }
    .status{font-size:13px;color:#6b7785;margin-top:10px}
    .error{color:#c0392b}
</style>
</head>
<body>

<div class="card">
    <h1>FITTRACK AI Assistant</h1>
    <div class="sub">Browser test — no Console required.</div>

    <textarea id="question" placeholder="Example: What is the current retention risk situation in the gym?"></textarea>

    <button id="askBtn">Ask FITTRACK AI</button>

    <div id="status" class="status"></div>
    <div id="result" class="result">AI response will appear here...</div>
</div>

<script>
const questionInput = document.getElementById("question");
const askBtn = document.getElementById("askBtn");
const statusEl = document.getElementById("status");
const resultEl = document.getElementById("result");

async function askAI() {
    const question = questionInput.value.trim();

    if (!question) {
        statusEl.textContent = "Please enter a question.";
        statusEl.className = "status error";
        return;
    }

    askBtn.disabled = true;
    statusEl.textContent = "Thinking...";
    statusEl.className = "status";
    resultEl.textContent = "";

    try {
        const response = await fetch("ai_assistant.php", {
            method: "POST",
            credentials: "same-origin",
            headers: {
                "Content-Type": "application/json",
                "Accept": "application/json"
            },
            body: JSON.stringify({
                question: question
            })
        });

        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(data.error || "AI Assistant request failed.");
        }

        statusEl.textContent = "Connected successfully.";
        resultEl.textContent = data.answer || "No answer returned.";
    } catch (error) {
        statusEl.textContent = "Request failed.";
        statusEl.className = "status error";
        resultEl.textContent = error.message;
    } finally {
        askBtn.disabled = false;
    }
}

askBtn.addEventListener("click", askAI);

questionInput.addEventListener("keydown", function(e) {
    if (e.key === "Enter" && (e.ctrlKey || e.metaKey)) {
        askAI();
    }
});
</script>

</body>
</html>
