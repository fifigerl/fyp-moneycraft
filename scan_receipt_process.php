<?php
session_start();

$userId = $_SESSION['id'];
$output = shell_exec("python scan_receipt.py 2>&1");

header('Content-Type: application/json');

if (strpos($output, "success")) {
    $data = json_decode($output, true);
    if (isset($data['amount'])) {
        echo json_encode([
            "success" => true,
            "amount" => $data['amount'],
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "error" => "No amount detected."
        ]);
    }
} else {
    echo json_encode([
        "success" => false,
        "error" => "Error processing the receipt: $output"
    ]);
}
?>
