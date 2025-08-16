document.addEventListener('click', async (e)=>{
  const b = e.target.closest('.ai-summary-btn');
  if(!b) return;
  b.disabled = true; b.textContent = (AIAutoSummaryFront?.i18n?.making) || 'Menyusun...';
  const pid = b.dataset.post;
  const len = (AIAutoSummaryFront?.defaultLen) || 100;
  try{
    const endpoint = (AIAutoSummaryFront?.restBase || '/wp-json/ai-summary/v1/') + 'summarize?post_id=' + encodeURIComponent(pid) + '&len=' + encodeURIComponent(len);
    const r = await fetch(endpoint, {credentials:'same-origin'});
    const j = await r.json();
    if (j?.data && j?.data?.status) {
      b.nextElementSibling.innerHTML = `<em>Gagal membuat ringkasan (${j.data.status}): ${j.message || 'Unknown error'}</em>`;
    } else if (j?.message && j?.code) {
      b.nextElementSibling.innerHTML = `<em>Gagal: ${j.message}</em>`;
    } else {
      b.nextElementSibling.innerHTML = j.html || '<em>Gagal membuat ringkasan</em>';
    }
  }catch(err){
    b.nextElementSibling.innerHTML = '<em>Gagal jaringan</em>';
  }finally{
    b.style.display='none';
  }
});