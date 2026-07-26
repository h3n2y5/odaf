@props(['id' => 'pdfViewerModal'])

<div x-data="securePdfViewer('{{ $id }}')" 
     @open-pdf-viewer.window="if ($event.detail.id === '{{ $id }}') { openModal($event.detail.url) }"
     x-show="isOpen" 
     class="relative z-50" 
     aria-labelledby="modal-title" role="dialog" aria-modal="true" x-cloak>

  <!-- Background backdrop -->
  <div x-show="isOpen" 
       x-transition:enter="ease-out duration-300"
       x-transition:enter-start="opacity-0"
       x-transition:enter-end="opacity-100"
       x-transition:leave="ease-in duration-200"
       x-transition:leave-start="opacity-100"
       x-transition:leave-end="opacity-0"
       class="fixed inset-0 bg-slate-900 bg-opacity-75 transition-opacity backdrop-blur-sm"></div>

  <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
    <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
      
      <!-- Modal panel -->
      <div x-show="isOpen" @click.away="closeModal()" 
           x-transition:enter="ease-out duration-300"
           x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
           x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
           x-transition:leave="ease-in duration-200"
           x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
           x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
           class="relative transform overflow-hidden rounded-xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-5xl"
           @contextmenu.prevent="() => {}"
           @keydown.window="handleKeydown($event)">
           
        <div class="absolute right-0 top-0 pr-4 pt-4 z-10">
          <button type="button" @click="closeModal()" class="rounded-md bg-white text-slate-400 hover:text-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
            <span class="sr-only">Tutup</span>
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
          <div class="sm:flex sm:items-start w-full">
            <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left w-full">
              <h3 class="text-lg font-semibold leading-6 text-slate-900 mb-4 flex items-center justify-center sm:justify-start gap-2" id="modal-title">
                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                Secure Document Viewer
                <span class="text-xs font-normal text-slate-500 ml-2 border border-slate-200 bg-slate-50 px-2 py-0.5 rounded-full">No Download</span>
              </h3>
              
              <div class="flex flex-col items-center w-full h-[75vh] overflow-auto bg-slate-200/50 border border-slate-200 rounded-lg select-none" id="pdf-container-{{ $id }}">
                  <div x-show="loading" class="text-slate-500 flex flex-col items-center justify-center h-full">
                      <svg class="animate-spin h-8 w-8 text-indigo-600 mb-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                      </svg>
                      <span class="font-medium">Memuat dokumen terenkripsi...</span>
                  </div>
              </div>
            </div>
          </div>
        </div>
        <div class="bg-slate-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6 border-t border-slate-200">
          <button type="button" @click="closeModal()" class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 sm:mt-0 sm:w-auto">Tutup</button>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('securePdfViewer', (id) => ({
            isOpen: false,
            loading: false,
            url: null,
            pdfDoc: null,
            containerId: 'pdf-container-' + id,

            init() {
                // Dynamically load pdf.js if not present
                if (typeof window.pdfjsLib === 'undefined') {
                    const script = document.createElement('script');
                    script.src = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js';
                    script.onload = () => {
                        window.pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
                    };
                    document.head.appendChild(script);
                }
            },

            openModal(newUrl) {
                if (!newUrl) return;
                this.url = newUrl;
                this.isOpen = true;
                
                // Wait for modal to be visible before rendering
                setTimeout(() => {
                    this.renderFile();
                }, 100);
            },
            
            closeModal() {
                this.isOpen = false;
                this.clearContainer();
            },

            clearContainer() {
                const container = document.getElementById(this.containerId);
                if (container) {
                    Array.from(container.children).forEach(child => {
                        if (child.tagName === 'CANVAS') {
                            container.removeChild(child);
                        }
                    });
                }
            },

            handleKeydown(e) {
                if (!this.isOpen) return;
                // Prevent Ctrl+S, Ctrl+P, Ctrl+C
                if ((e.ctrlKey || e.metaKey) && ['s', 'p', 'c'].includes(e.key.toLowerCase())) {
                    e.preventDefault();
                }
            },

            drawWatermark(ctx, width, height) {
                ctx.save();
                ctx.translate(width / 2, height / 2);
                ctx.rotate(-Math.PI / 4);
                
                const fontSize = Math.min(width, height) / 8;
                ctx.font = 'bold ' + fontSize + 'px sans-serif';
                ctx.fillStyle = 'rgba(150, 150, 150, 0.4)'; 
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                
                for(let i = -2; i <= 2; i++) {
                    for(let j = -2; j <= 2; j++) {
                        if ((i+j) % 2 === 0) {
                            ctx.fillText('CONFIDENTIAL', i * fontSize * 6, j * fontSize * 3);
                        }
                    }
                }
                
                ctx.restore();
            },

            async renderFile() {
                if (!this.url) return;
                
                // Cek ekstensi
                const lowerUrl = this.url.toLowerCase().split('?')[0];
                const isPdf = lowerUrl.endsWith('.pdf');

                this.loading = true;
                const container = document.getElementById(this.containerId);
                this.clearContainer();

                try {
                    if (isPdf) {
                        await this.renderPdf(container);
                    } else {
                        await this.renderImage(container);
                    }
                } catch (error) {
                    console.error('Error rendering file:', error);
                    alert('Gagal memuat dokumen. Pastikan file valid.');
                } finally {
                    this.loading = false;
                }
            },

            async renderImage(container) {
                return new Promise((resolve, reject) => {
                    const img = new Image();
                    img.crossOrigin = 'Anonymous';
                    img.onload = () => {
                        const canvas = document.createElement('canvas');
                        const ctx = canvas.getContext('2d');
                        
                        // Scale down if image is too huge, else keep original
                        let scale = 1.0;
                        if (img.width > 2000) scale = 2000 / img.width;
                        
                        canvas.width = img.width * scale;
                        canvas.height = img.height * scale;
                        canvas.className = 'my-4 shadow-lg border border-slate-300 max-w-[95%] bg-white';
                        canvas.style.userSelect = 'none';
                        canvas.style.pointerEvents = 'none';
                        
                        ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
                        this.drawWatermark(ctx, canvas.width, canvas.height);
                        
                        container.appendChild(canvas);
                        resolve();
                    };
                    img.onerror = (e) => reject(e);
                    img.src = this.url;
                });
            },

            async renderPdf(container) {
                if (typeof window.pdfjsLib === 'undefined') {
                    setTimeout(() => this.renderPdf(container), 200);
                    return;
                }

                try {
                    const loadingTask = window.pdfjsLib.getDocument(this.url);
                    this.pdfDoc = await loadingTask.promise;
                    
                    for (let pageNum = 1; pageNum <= this.pdfDoc.numPages; pageNum++) {
                        const page = await this.pdfDoc.getPage(pageNum);
                        
                        // Set scale to 1.5 for better resolution on most screens
                        const viewport = page.getViewport({ scale: 1.5 });
                        
                        const canvas = document.createElement('canvas');
                        const ctx = canvas.getContext('2d');
                        canvas.height = viewport.height;
                        canvas.width = viewport.width;
                        canvas.className = 'my-4 shadow-lg border border-slate-300 max-w-[95%] bg-white';
                        // Add some inline styles to prevent highlighting/dragging
                        canvas.style.userSelect = 'none';
                        canvas.style.pointerEvents = 'none';
                        
                        container.appendChild(canvas);
                        
                        const renderContext = {
                            canvasContext: ctx,
                            viewport: viewport
                        };
                        
                        await page.render(renderContext).promise;
                        
                        this.drawWatermark(ctx, canvas.width, canvas.height);
                    }
                } catch (error) {
                    console.error('Error rendering PDF:', error);
                    alert('Gagal memuat dokumen. Pastikan file valid.');
                } finally {
                    this.loading = false;
                }
            }
        }));
    });
</script>
