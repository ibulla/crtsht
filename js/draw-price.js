(()=>{
  const fmt=n=>'CHF '+Number(n).toLocaleString('de-CH',{minimumFractionDigits:2,maximumFractionDigits:2});
  const bodyText=document.body.textContent||'';
  const match=bodyText.match(/R-[A-F0-9]{10}/);
  const url=match?'/draw-price.php?reservation='+encodeURIComponent(match[0]):'/draw-price.php';
  fetch(url,{credentials:'same-origin',cache:'no-store'})
    .then(r=>r.ok?r.json():null)
    .then(data=>{
      if(!data||!data.ok||data.unit_price==null)return;
      const unit=Number(data.unit_price);
      document.querySelectorAll('.entry-grid .entry').forEach((entry,i)=>{
        const strong=entry.querySelector('strong');
        if(!strong)return;
        let price=entry.querySelector('.draw-price');
        if(!price){price=document.createElement('span');price.className='draw-price';price.style.display='block';price.style.marginTop='8px';price.style.fontSize='11px';price.style.letterSpacing='.04em';strong.insertAdjacentElement('afterend',price);}
        price.textContent=fmt(unit*(i+1));
      });
      const terminal=document.querySelector('.terminal-copy');
      if(terminal&&!document.querySelector('[data-public-entry-price]')){
        const p=document.createElement('p');p.dataset.publicEntryPrice='1';p.className='capacity';p.innerHTML='<strong>'+fmt(unit)+'</strong> / ENTRY';terminal.insertAdjacentElement('afterend',p);
      }
      if(match&&data.total_price!=null){
        const box=document.querySelector('.receipt-box');
        if(box&&!box.querySelector('[data-price-line]')){
          const line=document.createElement('div');line.className='receipt-line';line.dataset.priceLine='1';line.innerHTML='<span>PRICE</span><strong>'+fmt(data.total_price)+'</strong>';box.appendChild(line);
        }
      }
    }).catch(()=>{});
})();
