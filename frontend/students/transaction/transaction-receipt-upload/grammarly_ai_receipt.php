<div class="flex-1">

    <div class="bg-white shadow p-4">
        <h1 class="text-xl font-semibold">Upload Transaction Receipt</h1>
    </div>

    <div class="p-6">

        <form method="POST" enctype="multipart/form-data"
            action="../../backend/actions/upload-receipt/upload_grammarly_ai_receipt.php">

            <input type="hidden" name="round" value="<?php echo $_GET['round']; ?>">

            <input type="file" name="receipt_file" required
                class="border p-2 w-full">

            <button type="submit"
                class="mt-4 bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                Upload Receipt
            </button>
        </form>

    </div>
</div>