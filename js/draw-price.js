(()=>{
  const fmtCHF=n=>'CHF '+Number(n).toLocaleString('de-CH',{minimumFractionDigits:0,maximumFractionDigits:2});
  const fmtCrypto=(n,symbol)=>{
    const digits=symbol==='BTC'?6:5;
    return '≈ '+Number(n).toLocaleString('en-US',{minimumFractionDigits:digits,maximumFractionDigits:digits})+' '+symbol;
  };
  const bodyText=document.body.textContent||'';
  const reservationMatch=bodyText.match(/R-[A-F0-9]{10}/);
  const priceUrl=reservationMatch?'/draw-price.php?reservation='+encodeURIComponent(reservationMatch[0]):'/draw-price.php';
  let packagePrices=null;
  let crypto=null;
  let driftTimer=null;

  const drift=()=>{
    const sign=Math.random()<.5?-1:1;
    return 1+(sign*(1+Math.random()*2)/100);
  };

  const renderCrypto=()=>{
    if(!packagePrices||!crypto||!crypto.btc_chf||!crypto.eth_chf)return;
    document.querySelectorAll('.entry-grid .entry').forEach((entry,i)=>{
      const chf=Number(packagePrices[String(i+1)]||0);
      if(chf<=0)return;
      let box=entry.querySelector('.crypto-equivalent');
      if(!box){
        box=document.createElement('div');
        box.className='crypto-equivalent';
        box.style.marginTop='7px';
        box.style.fontSize='9px';
        box.style.lineHeight='1.55';
        box.style.letterSpacing='.035em';
        box.style.opacity='.68';
        const price=entry.querySelector('.draw-price');
        (price||entry.querySelector('strong'))?.insertAdjacentElement('afterend',box);
      }
      const btc=(chf/Number(crypto.btc_chf))*drift();
      const eth=(chf/Number(crypto.eth_chf))*drift();
      box.innerHTML='<span>'+fmtCrypto(btc,'BTC')+'</span><br><span>'+fmtCrypto(eth,'ETH')+'</span>';
    });
  };

  Promise.all([
    fetch(priceUrl,{credentials:'same-origin',cache:'no-store'}).then(r=>r.ok?r.json():null),
    reservationMatch?Promise.resolve(null):fetch('/crypto-rate.php',{credentials:'same-origin',cache:'no-store'}).then(r=>r.ok?r.json():null)
  ]).then(([data,rates])=>{
    if(!data||!data.ok)return;
    if(!reservationMatch&&data.prices){
      packagePrices=data.prices;
      document.querySelectorAll('.entry-grid .entry').forEach((entry,i)=>{
        const strong=entry.querySelector('strong');
        const priceValue=Number(data.prices[String(i+1)]||0);
        if(!strong||priceValue<=0)return;
        let price=entry.querySelector('.draw-price');
        if(!price){price=document.createElement('span');price.className='draw-price';price.style.display='block';price.style.marginTop='8px';price.style.fontSize='11px';price.style.letterSpacing='.04em';strong.insertAdjacentElement('afterend',price);}
        price.textContent=fmtCHF(priceValue);
      });
      const terminal=document.querySelector('.terminal-copy');
      if(terminal&&!document.querySelector('[data-public-entry-price]')){
        const vals=[1,2,3].map(q=>Number(data.prices[String(q)]||0));
        if(vals.some(v=>v>0)){
          const p=document.createElement('p');p.dataset.publicEntryPrice='1';p.className='capacity';
          p.innerHTML=vals.map((v,i)=>v>0?'<strong>'+fmtCHF(v)+'</strong> / '+(i+1)+'×':'').filter(Boolean).join(' &nbsp; · &nbsp; ');
          terminal.insertAdjacentElement('afterend',p);
        }
      }
      if(rates&&rates.ok){
        crypto=rates;
        renderCrypto();
        driftTimer=setInterval(renderCrypto,2300+Math.random()*1800);
        const action=document.querySelector('.terminal-action');
        if(action&&!document.querySelector('[data-price-lock-note]')){
          const note=document.createElement('span');
          note.dataset.priceLockNote='1';
          note.className='terminal-note';
          note.innerHTML='<strong>CHF PRICE FIXED ON CONFIRMATION.</strong><br>BTC / ETH are live reference values with ±1–3% display drift.';
          action.appendChild(note);
        }
      }
    }
    if(reservationMatch&&data.total_price!=null){
      const box=document.querySelector('.receipt-box');
      if(box&&!box.querySelector('[data-price-line]')){
        const line=document.createElement('div');line.className='receipt-line';line.dataset.priceLine='1';line.innerHTML='<span>PRICE</span><strong>'+fmtCHF(data.total_price)+'</strong>';box.appendChild(line);
      }
    }
  }).catch(()=>{});

  const form=document.querySelector('.draw-form');
  if(form&&form.getAttribute('action')==='/draw-standby.php'){
    const button=form.querySelector('.terminal-button');
    if(button){button.disabled=false;button.textContent='JOIN STANDBY';}
    const consent=form.querySelector('.form-consent');
    if(consent)consent.innerHTML='All 128 physical slots are currently held. Submitting stores your request in chronological <strong>STANDBY</strong>. No physical CRTSHT is held until a slot is released and your request is promoted.';
  }
})();
