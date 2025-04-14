<?php
/**
 * Reusable loading screen component
 * Usage: include 'components/loading-screen.php';
 */
?>
<div id="loading-screen" class="loading-screen" style="display: none;">
    <div class="loading-content">
        <div class="spinner-border text-primary" role="status">
            <span class="sr-only">Loading...</span>
        </div>
        <h3 class="loading-text mt-3">Loading...</h3>
    </div>
</div>

<style>
.loading-screen {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: #ffffff;
    z-index: 9999;
    display: flex;
    justify-content: center;
    align-items: center;
    transition: opacity 0.5s ease-out;
}

.loading-content {
    text-align: center;
}

.loading-text {
    color: #007bff;
    font-weight: 500;
}

/* Spinner size */
.spinner-border {
    width: 3rem;
    height: 3rem;
}
</style>