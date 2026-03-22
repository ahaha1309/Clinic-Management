<?php

function appointmentTemplate($title, $content) {

    return "
<div style='font-family:Arial;background:#f4f6f9;padding:20px'>
    <div style='max-width:600px;margin:auto;background:white;padding:30px;border-radius:10px'>
        
        <div style='text-align:center'>
            <img src='cid:logo_cid' style='max-width:150px;margin-bottom:10px;' />
            <h2 style='color:#2c3e50'>{$title}</h2>
        </div>

        <div style='margin-top:20px;font-size:15px;color:#333'>
            {$content}
        </div>

        <hr style='margin:30px 0'>
        <p style='font-size:13px;color:#777;text-align:center'>
            Phòng khám ABC<br>
            Hotline: 0909 999 999
        </p>
    </div>
</div>
";
}