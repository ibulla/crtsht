(()=>{
  const brands=[...document.querySelectorAll('.brand')];
  if(!brands.length) return;

  const variants=[
    '(RYP705H17.1NF0',
    '[rYp70$H!7.1Nf0',
    '<Я¥P70$H|+.[N|=0',
    'CR¥P70$H!7.1NF0'
  ];

  // Logical positions spell CRYPTOSHIT.INFO. Multi-character glyphs such as |="
  // stay one mutation slot, so the logo may subtly change width while it evolves.
  const slots=[
    ['C','(','[','<'],
    ['R','r','Я'],
    ['Y','y','¥'],
    ['P','p'],
    ['T','7','+'],
    ['O','0'],
    ['S','5','$'],
    ['H','h'],
    ['I','1','!','|'],
    ['T','7','+'],
    ['.'],
    ['I','1','['],
    ['N','n'],
    ['F','f','|='],
    ['O','0']
  ];

  const pick=list=>list[Math.floor(Math.random()*list.length)];
  const reduced=window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  brands.forEach(brand=>{
    if(brand.dataset.leetspeakReady==='1') return;
    brand.dataset.leetspeakReady='1';

    // Use one authored logo as a semantic/no-JS fallback, then let every slot mutate independently.
    brand.setAttribute('aria-label','CRYPTOSHIT.INFO');
    brand.textContent='';

    const nodes=slots.map(options=>{
      const span=document.createElement('span');
      span.textContent=pick(options);
      span.setAttribute('aria-hidden','true');
      brand.appendChild(span);
      return {span,options};
    });

    if(reduced) return;

    const candidates=nodes.filter(node=>node.options.length>1);
    const mutate=()=>{
      const node=pick(candidates);
      const current=node.span.textContent;
      let next=current;
      while(next===current) next=pick(node.options);
      node.span.textContent=next;
      window.setTimeout(mutate,1200+Math.random()*3800);
    };

    window.setTimeout(mutate,1800+Math.random()*2500);
  });
})();
