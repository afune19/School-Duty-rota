<?php
session_start();
include 'db.php';

/* ===========================
   ACTION HANDLERS
   =========================== */

// Delete teacher (per row)
if (isset($_GET['delete_id'])) {
    $id = intval($_GET['delete_id']);
    $conn->query("DELETE FROM teachers WHERE id=$id");
    $_SESSION['msg'] = "Teacher #$id deleted.";
    header("Location: dashboard.php?modal=1"); exit;
}

// Delete all teachers
if (isset($_POST['delete_all'])) {
    $conn->query("TRUNCATE TABLE teachers");
    $_SESSION['msg'] = "All teachers deleted.";
    header("Location: dashboard.php?modal=1"); exit;
}

// CSV upload (name,gender)
if (isset($_POST['upload'])) {
    if (!empty($_FILES['csv']['tmp_name']) && $_FILES['csv']['error'] === 0) {
        $file = fopen($_FILES['csv']['tmp_name'], 'r');
        while (($data = fgetcsv($file, 1000, ',')) !== false) {
            if (count($data) < 2) continue;
            $name = trim($data[0]);
            $gender = ucfirst(strtolower(trim($data[1])));
            // Map to ENUM
            if ($gender === 'Male') $gender = 'M';
            elseif ($gender === 'Female') $gender = 'F';
            else continue;

            $name = $conn->real_escape_string($name);
            $gender = $conn->real_escape_string($gender);
            $conn->query("INSERT INTO teachers (name, gender) VALUES ('$name','$gender')");
        }
        fclose($file);
        $_SESSION['msg'] = "Teachers uploaded successfully.";
    } else {
        $_SESSION['msg'] = "CSV upload failed or file empty.";
    }
    header("Location: dashboard.php"); exit;
}

// Generate rota
if (isset($_POST['generate'])) {
    $weeks   = max(1, intval($_POST['weeks'] ?? 0));
    $opening = intval($_POST['opening'] ?? 0);
    $midterm = intval($_POST['midterm'] ?? 0);
    $closing = intval($_POST['closing'] ?? 0);

    // Clear existing rota
    $conn->query("TRUNCATE rota");

    // Fetch teachers
$teachers = [];
$res = $conn->query("SELECT * FROM teachers WHERE active=1");
while ($row = $res->fetch_assoc()) $teachers[] = $row;

// Separate by gender (match ENUM 'M' / 'F')
$males = array_values(array_filter($teachers, fn($t) => $t['gender'] === 'M'));
$fems  = array_values(array_filter($teachers, fn($t) => $t['gender'] === 'F'));

if (count($males) < 1 || count($fems) < 1) {
    $_SESSION['msg'] = "Need at least one male and one female teacher.";
    header("Location: dashboard.php"); exit;
}

    /* ================================
       WEEKLY DUTY
       ================================ */
    $used = []; // track used teachers to avoid repeats until necessary
    for ($w = 1; $w <= $weeks; $w++) {
        if (count($used) >= count($teachers)) {
            // reset after all teachers have been used
            $used = [];
        }

        // pick Male not in $used
        $maleChoices = array_values(array_filter($males, fn($t) => !in_array($t['id'], $used)));
        if (empty($maleChoices)) $maleChoices = $males; // fallback
        $t1 = $maleChoices[array_rand($maleChoices)];
        $used[] = $t1['id'];

        // pick Female not in $used
        $femChoices = array_values(array_filter($fems, fn($t) => !in_array($t['id'], $used)));
        if (empty($femChoices)) $femChoices = $fems; // fallback
        $t2 = $femChoices[array_rand($femChoices)];
        $used[] = $t2['id'];

        $names = $t1['name'] . ", " . $t2['name'];

        $label = "Week $w";
        if ($w === $opening) $label .= " (Opening)";
        if ($w === $midterm) $label .= " (Midterm)";
        if ($w === $closing) $label .= " (Closing)";

        $conn->query("INSERT INTO rota (week, teacher, duty_type) VALUES ('$label', '$names', 'WEEKLY')");
    }

    /* ================================
       LUNCH DUTY (Mon–Fri, fixed for term)
       ================================ */
    $days = ['Monday','Tuesday','Wednesday','Thursday','Friday'];

    $malePool = $males;
    $femalePool = $fems;
    shuffle($malePool);
    shuffle($femalePool);

    $indexM = 0; $indexF = 0;

    foreach ($days as $day) {
        // Ensure enough teachers, reshuffle & reset index if needed
        if ($indexM + 2 > count($malePool)) {
            shuffle($malePool); $indexM = 0;
        }
        if ($indexF + 2 > count($femalePool)) {
            shuffle($femalePool); $indexF = 0;
        }

        $chunkM = array_slice($malePool, $indexM, 2);
        $chunkF = array_slice($femalePool, $indexF, 2);

        $indexM += 2;
        $indexF += 2;

        $names = implode(", ", array_merge(
            array_column($chunkM, 'name'),
            array_column($chunkF, 'name')
        ));

        $conn->query("INSERT INTO rota (week, teacher, duty_type) VALUES ('$day', '$names', 'LUNCH')");
    }

    $_SESSION['msg'] = "Duty rota generated.";
    header("Location: dashboard.php"); exit;
}

// Export Weekly PDF
if (isset($_POST['export_weekly'])) {
    require_once('tcpdf/tcpdf.php');
    $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetMargins(10, 10, 10);
    $pdf->AddPage();

    // Title
    $pdf->SetFont('helvetica','B',20);
    $pdf->Cell(0,12,"ST. MARY'S COMPREHENSIVE SCHOOL - WEEKLY DUTY ROTA",0,1,'C');
    $pdf->Ln(8);

    // Table header
    $pdf->SetFont('helvetica','B',30);
    $pdf->SetFillColor(200,220,255);
    $pdf->Cell(80,14,"Week",1,0,'C',true);
    $pdf->Cell(200,14,"Teacher(s)",1,1,'C',true);

    // Data
    $pdf->SetFont('helvetica','B',25);
    $res = $conn->query("SELECT week, teacher FROM rota WHERE duty_type='WEEKLY'");
    while ($r = $res->fetch_assoc()) {
        $pdf->Cell(80,18,$r['week'],1,0,'C');
        $pdf->Cell(200,18,$r['teacher'],1,1,'C');
    }

    ob_end_clean();
    $pdf->Output('weekly_rota.pdf', 'D');
    exit;
}

// Export Lunch PDF
if (isset($_POST['export_lunch'])) {
    require_once('tcpdf/tcpdf.php');
    $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetMargins(10, 10, 10);
    $pdf->AddPage();

    // Title
    $pdf->SetFont('helvetica','B',20);
    $pdf->Cell(0,12,"ST. MARY'S COMPREHENSIVE SCHOOL - LUNCH DUTY ROTA",0,1,'C');
    $pdf->Ln(8);

    // Table header
    $pdf->SetFont('helvetica','B',30);
    $pdf->SetFillColor(200,220,255);
    $pdf->Cell(50,14,"Day",1,0,'C',true);
    $pdf->Cell(220,14,"Teacher(s)",1,1,'C',true);

    // Data
    $pdf->SetFont('helvetica','B',17);
    $res = $conn->query("SELECT week, teacher FROM rota WHERE duty_type='LUNCH'");
    while ($r = $res->fetch_assoc()) {
        $pdf->Cell(50,25,$r['week'],1,0,'C');
        $pdf->Cell(220,25,$r['teacher'],1,1,'C');
    }

    ob_end_clean();
    $pdf->Output('lunch_rota.pdf', 'D');
    exit;
}

?>

<!DOCTYPE html>
<html>
<head>
<title>ST. MARY'S COMPREHENSIVE SCHOOL DUTY ROTA</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
:root{
--blue:#1e90ff;--maroon:#800000;--bg:#f4f6f9;--card:#fff;--text:#1f2937;--muted:#64748b;
}
body{font-family:Arial,sans-serif;background:var(--bg);color:var(--text);margin:0;}
h1{text-align:center;color:var(--maroon);margin:20px 0;}
.wrap{max-width:1100px;margin:0 auto;padding:16px;}
.cards{display:grid;gap:16px;grid-template-columns:1fr;}
@media(min-width:900px){.cards{grid-template-columns:1fr 1fr}}
.card{background:var(--card);border-radius:16px;padding:18px;box-shadow:0 10px 25px rgba(0,0,0,.08);transition:.25s transform,.25s box-shadow;}
.card:hover{transform:translateY(-4px);box-shadow:0 16px 35px rgba(0,0,0,.12);}
.header{display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:10px;}
.title{font-weight:700;color:var(--maroon);}
.tag{font-size:12px;color:var(--muted);background:#eef6ff;padding:4px 10px;border-radius:999px;}
input,select{padding:10px;border:1px solid #d1d5db;border-radius:10px;width:100%;outline:none;}
.row{display:grid;gap:12px;grid-template-columns:1fr;}
@media(min-width:720px){.row.two{grid-template-columns:1fr 1fr}.row.four{grid-template-columns:1fr 1fr 1fr 1fr}}
.btn{background:linear-gradient(135deg,var(--blue),#3aa0ff);border:none;color:#fff;padding:10px 16px;border-radius:12px;font-weight:700;cursor:pointer;transition:transform .1s ease, box-shadow .2s ease;box-shadow:0 6px 0 rgba(30,144,255,.4);}
.btn:hover{transform:translateY(-2px);}
.btn:active{transform:translateY(2px);box-shadow:0 2px 0 rgba(30,144,255,.4);}
.btn.alt{background:linear-gradient(135deg,var(--maroon),#ad2e2e);box-shadow:0 6px 0 rgba(128,0,0,.35);}
.btn.ghost{background:#fff;color:var(--blue);border:2px solid var(--blue);box-shadow:none;}
table{width:100%;border-collapse:collapse;margin-top:10px;}
th,td{border:1px solid #e5e7eb;padding:10px;text-align:center;}
th{background:var(--blue);color:#fff;}
.msg{margin:10px 0;padding:10px;border-radius:12px;background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46;}

/* Modal */
.modal{display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:50;align-items:center;justify-content:center;padding:20px;}
.modal .panel{background:var(--card);width:min(100%,1000px);max-height:90vh;overflow:auto;border-radius:16px;box-shadow:0 20px 60px rgba(0,0,0,.25);padding:18px;}
.modal .top{display:flex;align-items:center;justify-content:space-between;}
.close{background:#fff;border:2px solid var(--maroon);color:var(--maroon);padding:6px 10px;border-radius:10px;font-weight:700;cursor:pointer;}
</style>
</head>
<body>
<h1>ST. MARY'S COMPREHENSIVE SCHOOL DUTY ROTA</h1>
<div style="text-align:center; margin-bottom:20px;">
    <a href="logout.php" class="btn alt">Logout</a>
</div>

<div class="wrap">
<?php if(!empty($_SESSION['msg'])){echo "<div class='msg'>".htmlspecialchars($_SESSION['msg'])."</div>";unset($_SESSION['msg']);} ?>

<div class="cards">
<!-- Upload -->
<div class="card">
<div class="header"><div class="title">Upload Teachers (CSV: name,gender)</div><span class="tag">Teachers</span></div>
<form method="post" enctype="multipart/form-data" class="row two">
<input type="file" name="csv" accept=".csv" required>
<div style="display:flex;gap:8px;justify-content:flex-end">
<button class="btn" type="submit" name="upload">Upload</button>
<button class="btn alt" type="button" onclick="openModal()">View Teachers</button>
</div>
</form>
</div>

<!-- Generate -->
<div class="card">
<div class="header"><div class="title">⚡ Generate Duty Rota</div><span class="tag">Weekly + Lunch</span></div>
<form method="post">
<div class="row two">
<div><label>Number of weeks</label><input type="number" name="weeks" min="1" required></div>
<div><label>Opening week</label><input type="number" name="opening" min="1" required></div>
<div><label>Midterm week</label><input type="number" name="midterm" min="1" required></div>
<div><label>Closing week</label><input type="number" name="closing" min="1" required></div>
</div>
<div style="margin-top:10px"><button class="btn" type="submit" name="generate">Generate</button></div>
</form>
</div>
</div>

<!-- Weekly list -->
<div class="card">
<div class="header"><div class="title">📑 Weekly Duty Rota</div><span class="tag">Two teachers per week</span></div>
<table><tr><th>Week</th><th>Teachers</th></tr>
<?php
$res = $conn->query("SELECT week, teacher FROM rota WHERE duty_type='WEEKLY'");
while($row = $res->fetch_assoc()){
    echo "<tr><td>".htmlspecialchars($row['week'])."</td><td>".htmlspecialchars($row['teacher'])."</td></tr>";
}
?>
</table>
<form method="post" style="margin-top:10px"><button class="btn alt" type="submit" name="export_weekly">Export Weekly PDF</button></form>
</div>

<!-- Lunch list -->
<div class="card">
<div class="header"><div class="title">🍴 Lunch Duty Rota</div><span class="tag">4 teachers per day</span></div>
<table><tr><th>Day</th><th>Teachers</th></tr>
<?php
$res = $conn->query("SELECT week, teacher FROM rota WHERE duty_type='LUNCH'");
while($row = $res->fetch_assoc()){
    echo "<tr><td>".htmlspecialchars($row['week'])."</td><td>".htmlspecialchars($row['teacher'])."</td></tr>";
}
?>
</table>
<form method="post" style="margin-top:10px"><button class="btn alt" type="submit" name="export_lunch">Export Lunch PDF</button></form>
</div>

</div>

<!-- Modal -->
<div id="modal" class="modal">
<div class="panel">
<div class="top">
<h3 style="margin:0;color:var(--maroon)">Teachers List</h3>
<button class="close" onclick="closeModal()">Close</button>
</div>
<form method="post"><button class="btn alt" name="delete_all" type="submit" style="margin:10px 0">Delete All Teachers</button></form>
<table>
<tr><th>ID</th><th>Name</th><th>Gender</th><th>Action</th></tr>
<?php
$res = $conn->query("SELECT * FROM teachers ORDER BY id ASC");
while($t = $res->fetch_assoc()){
    $id = (int)$t['id'];
    $name = htmlspecialchars($t['name']);
    $gender = $t['gender']==='F'?'Female':'Male';
    echo "<tr>
    <td>$id</td>
    <td>$name</td>
    <td>$gender</td>
    <td><a class='btn ghost' href='?delete_id=$id&modal=1' onclick=\"return confirm('Delete this teacher?')\">Delete</a></td>
    </tr>";
}
?>
</table>
</div>
</div>

<script>
function openModal(){document.getElementById('modal').style.display='flex';}
function closeModal(){document.getElementById('modal').style.display='none';}
<?php if(isset($_GET['modal']) && $_GET['modal']=='1'){echo "window.addEventListener('DOMContentLoaded', openModal);";} ?>
document.addEventListener('click',function(e){if(e.target===document.getElementById('modal')) closeModal();});
</script>
</body>
</html>
