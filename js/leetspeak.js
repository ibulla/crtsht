(()=>{
  const brands=[...document.querySelectorAll('.brand')];
  const authored={
    '(RYP705H17.1NF0':['(','R','Y','P','7','0','5','H','1','7','.','1','N','F','0'],
    '[rYp70$H!7.1Nf0':['[','r','Y','p','7','0','$','H','!','7','.','1','N','f','0'],
    '<Я¥P70$H|+.[N|=0':['<','Я','¥','P','7','0','$','H','|','+','.','[','N','|=','0'],
    'CR¥P70$H!7.1NF0':['C','R','¥','P','7','0','$','H','!','7','.','1','N','F','0']
  };
  const slots=[['C','(','[','<'],['R','r','Я'],['Y','y','¥'],['P','p'],['T','7','+'],['O','0'],['S','5','$'],['H','h'],['I','1','!','|'],['T','7','+'],['.'],['I','1','['],['N','n'],['F','f','|='],['O','0']];
  const pick=list=>list[Math.floor(Math.random()*list.length)];
  const reduced=window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  brands.forEach(brand=>{
    if(brand.dataset.leetspeakReady==='1') return;
    brand.dataset.leetspeakReady='1';
    const original=brand.textContent.trim();
    const seed=authored[original]||slots.map(pick);
    brand.setAttribute('aria-label','CRYPTOSHIT.INFO');brand.textContent='';
    const nodes=slots.map((options,index)=>{const span=document.createElement('span');span.textContent=seed[index]??pick(options);span.setAttribute('aria-hidden','true');brand.appendChild(span);return{span,options};});
    if(reduced)return;
    const candidates=nodes.filter(node=>node.options.length>1);
    const mutate=()=>{const node=pick(candidates),current=node.span.textContent;let next=current;while(next===current)next=pick(node.options);node.span.textContent=next;window.setTimeout(mutate,1200+Math.random()*3800);};
    window.setTimeout(mutate,1800+Math.random()*2500);
  });

  // Public draw: stable deep-link target directly at the reservation terminal.
  if(location.pathname.replace(/\/+$/,'')==='/draw'){
    const form=document.querySelector('.draw-form');
    const terminal=form?.closest('.system-window');
    if(terminal){
      terminal.id='draw-terminal';
      terminal.style.scrollMarginTop='18px';
      if(location.hash==='#draw-terminal') requestAnimationFrame(()=>terminal.scrollIntoView({block:'start'}));
    }

    const sentNote=[...document.querySelectorAll('.terminal-note')].find(el=>el.textContent.includes('has been sent to your email address'));
    if(sentNote&&!sentNote.dataset.spamNote){
      sentNote.dataset.spamNote='1';
      sentNote.append(document.createElement('br'),'Check your spam folder. Shit tends to go there.');
    }
  }

  // Archive landing page: temporary terminal window announcing the live draw.
  if(location.pathname==='/'&&!sessionStorage.getItem('crtshtVoucherDismissed')){
    const style=document.createElement('style');
    style.textContent=`
      .crt-voucher-overlay{position:fixed;inset:0;z-index:9998;background:rgba(17,17,17,.16);display:grid;place-items:center;padding:20px;font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,"Liberation Mono",monospace}
      .crt-voucher-window{width:min(560px,100%);background:var(--bg,#f2f2ee);color:var(--fg,#111);border:1px solid currentColor;box-shadow:10px 10px 0 rgba(17,17,17,.16)}
      .crt-voucher-bar{display:flex;justify-content:space-between;align-items:center;gap:18px;border-bottom:1px solid currentColor;padding:8px 10px;font-size:10px;letter-spacing:.08em;text-transform:uppercase}
      .crt-voucher-close{appearance:none;border:0;background:none;color:inherit;font:inherit;font-size:18px;line-height:1;cursor:pointer;padding:0 2px}
      .crt-voucher-body{padding:22px}
      .crt-voucher-kicker{font-size:10px;letter-spacing:.08em;text-transform:uppercase;margin:0 0 8px}
      .crt-voucher-title{font-size:clamp(30px,6vw,54px);line-height:.9;letter-spacing:-.055em;margin:0 0 16px;max-width:10ch}
      .crt-voucher-copy{font-size:12px;line-height:1.55;max-width:48ch;margin:0 0 22px}
      .crt-voucher-action{display:inline-flex;align-items:center;justify-content:center;border:1px solid currentColor;background:var(--fg,#111);color:var(--bg,#f2f2ee);padding:12px 15px;font-size:11px;letter-spacing:.07em;text-transform:uppercase;text-decoration:none}
      .crt-voucher-action:hover{background:transparent;color:inherit;text-decoration:none}
      .crt-voucher-status{border-top:1px solid currentColor;padding:8px 10px;font-size:9px;letter-spacing:.06em;text-transform:uppercase;display:flex;justify-content:space-between;gap:15px;flex-wrap:wrap}
      @media(max-width:600px){.crt-voucher-overlay{place-items:end center;padding:12px}.crt-voucher-window{box-shadow:6px 6px 0 rgba(17,17,17,.16)}.crt-voucher-body{padding:18px}}
    `;
    document.head.appendChild(style);

    const overlay=document.createElement('div');
    overlay.className='crt-voucher-overlay';
    overlay.setAttribute('role','dialog');
    overlay.setAttribute('aria-modal','true');
    overlay.setAttribute('aria-label','CRTSHT draw vouchers');
    overlay.innerHTML=`
      <div class="crt-voucher-window">
        <div class="crt-voucher-bar"><span>CRTSHT / DISPERSAL TERMINAL</span><button class="crt-voucher-close" type="button" aria-label="Close">×</button></div>
        <div class="crt-voucher-body">
          <p class="crt-voucher-kicker">RESERVATIONS OPEN / DRAW 01</p>
          <h2 class="crt-voucher-title">VOUCHERS ARE AVAILABLE.</h2>
          <p class="crt-voucher-copy">Reserve 1–3 vouchers for the next draw. You choose to own one. Chance chooses which CRTSHT becomes yours.</p>
          <a class="crt-voucher-action" href="/draw#draw-terminal">ENTER THE DRAW →</a>
        </div>
        <div class="crt-voucher-status"><span>128 PHYSICAL ORIGINALS</span><span>OPEN → RESERVED → PAID → ASSIGNED</span></div>
      </div>`;
    document.body.appendChild(overlay);

    const close=()=>{sessionStorage.setItem('crtshtVoucherDismissed','1');overlay.remove();style.remove();};
    overlay.querySelector('.crt-voucher-close')?.addEventListener('click',close);
    overlay.addEventListener('click',event=>{if(event.target===overlay)close();});
    document.addEventListener('keydown',event=>{if(event.key==='Escape'&&document.body.contains(overlay))close();},{once:true});
  }

  // Fill the single provenance tx-cost row from the mined receipt.
  const costRow=document.querySelector('[data-tx-cost-row]');
  const fallbackTxLink=document.querySelector('a[href^="https://etherscan.io/tx/0x"]');
  const tx=costRow?.dataset.tx||fallbackTxLink?.href.match(/0x[a-fA-F0-9]{64}/)?.[0];
  if(tx){
    fetch('/gas.php?tx='+encodeURIComponent(tx),{headers:{Accept:'application/json'}})
      .then(response=>response.ok?response.json():null)
      .then(data=>{
        if(!data?.ok||!data.fee_eth)return;
        const target=costRow?.querySelector('span:last-child');
        if(!target)return;
        const fee=Number(data.fee_eth),gas=Number(data.gas_used),gwei=Number(data.gas_price_gwei);
        target.classList.remove('muted');
        target.textContent=(Number.isFinite(fee)?fee.toFixed(6):data.fee_eth)+' ETH';
        if(Number.isFinite(gas)&&Number.isFinite(gwei)){
          const detail=document.createElement('span');detail.className='muted';detail.style.marginLeft='8px';detail.textContent='· '+gas.toLocaleString('en-US')+' gas @ '+gwei.toLocaleString('en-US',{maximumFractionDigits:4})+' Gwei';target.appendChild(detail);
        }
      })
      .catch(()=>{});
  }
})();
