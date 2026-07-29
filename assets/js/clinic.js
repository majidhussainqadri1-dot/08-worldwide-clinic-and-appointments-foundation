document.addEventListener('DOMContentLoaded',function(){document.querySelectorAll('.swc-doctor-form select[name="status"]').forEach(function(select){var form=select.closest('form'),box=form.querySelector('.swc-reschedule');function sync(){box.hidden=select.value!=='reschedule-requested';}select.addEventListener('change',sync);sync();});});

