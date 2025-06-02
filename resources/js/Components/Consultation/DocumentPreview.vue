<script setup>
import { ref, watch, onMounted, nextTick } from 'vue';
import { useToastr } from '../../Components/toster';
import { renderAsync } from 'docx-preview';
import axios from 'axios';
import { useSweetAlert } from '../../Components/useSweetAlert';

const toaster = useToastr();
const props = defineProps({
  previewContent: {
    type: String,
    default: ''
  },
  loading: {
    type: Boolean,
    default: false
  },
  selectedTemplates: {
    type: Array,
    default: () => []
  },
  templateData: {
    type: Object,
    default: () => ({})
  },
  // New prop for patient ID
  patientId: {
    type: [String, Number],
    required: true
  }
});

const emit = defineEmits(['update:previewContent', 'refresh', 'documentGenerated']);

const previewMode = ref(true);
const documentContent = ref(props.previewContent);
const editorRef = ref(null);
const wordFile = ref(null);
const uploadProgress = ref(0);
const templateFile = ref(null);
const isTemplate = ref(false);

// Watch for changes in the previewContent prop
watch(() => props.previewContent, (newValue) => {
  if (previewMode.value) {
    documentContent.value = newValue;
  }
});

// Enhanced file upload handler with template detection
const handleFileUpload = async (event) => {
  const file = event.target.files[0];
  if (!file) return;

  wordFile.value = file;

  const isTemplateFile = await detectTemplateFile(file);

  if (isTemplateFile) {
    isTemplate.value = true;
    templateFile.value = file;
    await handleTemplateFile(file);
  } else {
    isTemplate.value = false;
    await handleRegularDocxFile(file);
  }
};

const detectTemplateFile = async (file) => {
  try {
    const PizZip = (await import('pizzip')).default;
    const Docxtemplater = (await import('docxtemplater')).default;

    const content = await readFileAsArrayBuffer(file);
    const zip = new PizZip(content);
    const doc = new Docxtemplater(zip, {
      paragraphLoop: true,
      linebreaks: true,
    });

    const xmlContent = zip.files['word/document.xml']?.asText();

    const templatePatterns = [
      /{[^}]+}/g,
      /\$\{[^}]+\}/g,
      /\[\[.*?\]\]/g,
    ];

    return templatePatterns.some(pattern => pattern.test(xmlContent));
  } catch (error) {
    console.log('Template detection failed, treating as regular document');
    return false;
  }
};

const handleTemplateFile = async (file) => {
  try {
    const PizZip = (await import('pizzip')).default;
    const Docxtemplater = (await import('docxtemplater')).default;

    uploadProgress.value = 20;

    const content = await readFileAsArrayBuffer(file);
    const zip = new PizZip(content);

    uploadProgress.value = 40;

    const doc = new Docxtemplater(zip, {
      paragraphLoop: true,
      linebreaks: true,
    });

    const placeholders = extractPlaceholders(doc);

    uploadProgress.value = 60;

    const sampleData = generateSampleData(placeholders);
    const dataToUse = Object.keys(props.templateData).length > 0 ? props.templateData : sampleData;

    doc.setData(dataToUse);
    doc.render();

    uploadProgress.value = 80;

    const renderedBuffer = doc.getZip().generate({ type: 'arraybuffer' });
    const renderedBlob = new Blob([renderedBuffer], { type: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' });

    await renderDocxToHtml(renderedBlob);

    uploadProgress.value = 100;

    emit('documentGenerated', {
      type: 'template',
      placeholders,
      templateFile: file,
      renderedData: dataToUse
    });

  } catch (error) {
    console.error('Template processing error:', error);
    await handleRegularDocxFile(file);
  }
};

const handleRegularDocxFile = async (file) => {
  try {
    const { renderAsync } = await import('docx-preview');

    uploadProgress.value = 30;

    const tempContainer = document.createElement('div');
    document.body.appendChild(tempContainer);

    uploadProgress.value = 60;

    await renderAsync(file, tempContainer, null, {
      className: 'docx-wrapper',
      inWrapper: false,
      ignoreWidth: false,
      ignoreHeight: false,
      ignoreFonts: false,
      breakPages: false,
      ignoreLastRenderedPageBreak: false,
      experimental: false,
      trimXmlDeclaration: false,
      useBase64URL: true,
      renderHeaders: true,
      renderFooters: true,
      renderFootnotes: true,
      renderEndnotes: true
    });

    uploadProgress.value = 90;

    let renderedHTML = tempContainer.innerHTML;
    renderedHTML = cleanDocxPreviewHtml(renderedHTML);
    renderedHTML = preserveWordFormatting(renderedHTML);

    document.body.removeChild(tempContainer);

    documentContent.value = `
      <div class="document-content" contenteditable="true">
        ${renderedHTML}
      </div>
    `;

    uploadProgress.value = 100;

  } catch (error) {
    console.error("Docx-preview conversion error:", error);
    uploadProgress.value = 0;
  }
};

const extractPlaceholders = (doc) => {
  const placeholders = new Set();

  try {
    doc.setData({});
    doc.render();
  } catch (error) {
    const errorMessage = error.message || '';
    const matches = errorMessage.match(/tag "([^"]+)"/g);
    if (matches) {
      matches.forEach(match => {
        const placeholder = match.replace(/tag "|"/g, '');
        placeholders.add(placeholder);
      });
    }
  }

  return Array.from(placeholders);
};

const generateSampleData = (placeholders) => {
  const sampleData = {};

  placeholders.forEach(placeholder => {
    const lowerPlaceholder = placeholder.toLowerCase();

    if (lowerPlaceholder.includes('name')) {
      sampleData[placeholder] = 'John Doe';
    } else if (lowerPlaceholder.includes('date')) {
      sampleData[placeholder] = new Date().toLocaleDateString();
    } else if (lowerPlaceholder.includes('email')) {
      sampleData[placeholder] = 'john.doe@example.com';
    } else if (lowerPlaceholder.includes('company')) {
      sampleData[placeholder] = 'Acme Corporation';
    } else if (lowerPlaceholder.includes('address')) {
      sampleData[placeholder] = '123 Main Street, City, State 12345';
    } else if (lowerPlaceholder.includes('phone')) {
      sampleData[placeholder] = '+1 (555) 123-4567';
    } else if (lowerPlaceholder.includes('amount') || lowerPlaceholder.includes('price')) {
      sampleData[placeholder] = '$1,000.00';
    } else {
      sampleData[placeholder] = `[${placeholder}]`;
    }
  });

  return sampleData;
};

const renderDocxToHtml = async (blob) => {
  try {
    const { renderAsync } = await import('docx-preview');

    const tempContainer = document.createElement('div');
    document.body.appendChild(tempContainer);

    await renderAsync(blob, tempContainer, null, {
      className: 'docx-wrapper',
      inWrapper: false,
      ignoreWidth: false,
      ignoreHeight: false,
      ignoreFonts: false,
      breakPages: false,
      ignoreLastRenderedPageBreak: false,
      experimental: false,
      trimXmlDeclaration: false,
      useBase64URL: true,
      renderHeaders: true,
      renderFooters: true,
      renderFootnotes: true,
      renderEndnotes: true
    });

    let renderedHTML = tempContainer.innerHTML;
    renderedHTML = cleanDocxPreviewHtml(renderedHTML);
    renderedHTML = preserveWordFormatting(renderedHTML);

    document.body.removeChild(tempContainer);

    documentContent.value = `
      <div class="document-content" contenteditable="true">
        ${renderedHTML}
      </div>
    `;

  } catch (error) {
    console.error('Docx rendering error:', error);
  }
};

const generateDocumentWithData = async (newData) => {
  if (!templateFile.value) return;

  try {
    const PizZip = (await import('pizzip')).default;
    const Docxtemplater = (await import('docxtemplater')).default;

    const content = await readFileAsArrayBuffer(templateFile.value);
    const zip = new PizZip(content);
    const doc = new Docxtemplater(zip, {
      paragraphLoop: true,
      linebreaks: true,
    });

    doc.setData(newData);
    doc.render();

    const buffer = doc.getZip().generate({ type: 'arraybuffer' });
    const blob = new Blob([buffer], {
      type: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    });

    await renderDocxToHtml(blob);

    return blob;

  } catch (error) {
    console.error('Document generation error:', error);
    throw error;
  }
};

const downloadGeneratedDocument = async (data = props.previewContent) => {
  try {
    const blob = await generateDocumentWithData(data);
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `generated-document-${Date.now()}.docx`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
  } catch (error) {
    console.error('Download failed:', error);
    alert('Failed to generate document. Please check your template data.');
  }
};

// New Word Document Generation using Docxtemplater for better styling fidelity
const generateWordDocument = async () => {
  try {
    toaster.info('Generating Word document...');

    const PizZip = (await import('pizzip')).default;
    const Docxtemplater = (await import('docxtemplater')).default;

    let content = editorRef.value ? editorRef.value.innerHTML : documentContent.value;

    if (!content) {
      toaster.error('No content to generate document from');
      return;
    }

    // Create a temporary container to apply styles and process HTML elements
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = content;

    // Convert images to base64
    await convertImagesToBase64(tempDiv);

    // Apply inline styles to mimic preview
    const styledHtml = await createStyledHtmlForWord(tempDiv);

    // Minimal DOCX structure to insert the styled HTML
    const docxXml = `<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <w:body>
    ${convertHtmlToWordprocessingML(styledHtml)}
  </w:body>
</w:document>`;

    const zip = new PizZip();
    zip.file('word/document.xml', docxXml);
    zip.file('[Content_Types].xml', `<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>
</Types>`);
    zip.file('_rels/.rels', `<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>
</Relationships>`);

    const generatedDoc = new Docxtemplater(zip);
    generatedDoc.render(); // Render with empty data if no template placeholders

    const output = generatedDoc.getZip().generate({
      type: 'blob',
      mimeType: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    });

    const url = window.URL.createObjectURL(output);
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `document-${Date.now()}.docx`);
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);

    toaster.success('Word document generated successfully!');
  } catch (error) {
    console.error('Word generation error:', error);
    toaster.error('Failed to generate Word document');
  } finally {
    props.loading = false;
  }
};

// Helper to convert HTML to simplified WordprocessingML
const convertHtmlToWordprocessingML = (htmlString) => {
  const doc = new DOMParser().parseFromString(htmlString, 'text/html');
  let wordML = '';

  const processNode = (node) => {
    if (node.nodeType === Node.TEXT_NODE) {
      // Escape XML sensitive characters
      const text = node.textContent.replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&apos;');
      wordML += `<w:t>${text}</w:t>`;
    } else if (node.nodeType === Node.ELEMENT_NODE) {
      const tagName = node.tagName.toLowerCase();
      const styleAttr = node.getAttribute('style') || '';
      let styleXml = '';

      if (styleAttr) {
        // Basic inline style conversion to WordprocessingML properties
        if (styleAttr.includes('font-weight: bold')) styleXml += '<w:b/>';
        if (styleAttr.includes('font-style: italic')) styleXml += '<w:i/>';
        if (styleAttr.includes('text-decoration: underline')) styleXml += '<w:u w:val="single"/>';
        // Add more style conversions as needed
      }

      if (['p', 'div'].includes(tagName)) {
        wordML += `<w:p><w:r>${styleXml ? `<w:rPr>${styleXml}</w:rPr>` : ''}`;
        node.childNodes.forEach(processNode);
        wordML += `</w:r></w:p>`;
      } else if (['h1', 'h2', 'h3', 'h4', 'h5', 'h6'].includes(tagName)) {
        const headingLevel = tagName.charAt(1);
        wordML += `<w:p><w:pPr><w:pStyle w:val="Heading${headingLevel}"/></w:pPr><w:r>${styleXml ? `<w:rPr>${styleXml}</w:rPr>` : ''}`;
        node.childNodes.forEach(processNode);
        wordML += `</w:r></w:p>`;
      } else if (tagName === 'strong' || tagName === 'b') {
        wordML += `<w:r><w:rPr><w:b/></w:rPr>`;
        node.childNodes.forEach(processNode);
        wordML += `</w:r>`;
      } else if (tagName === 'em' || tagName === 'i') {
        wordML += `<w:r><w:rPr><w:i/></w:rPr>`;
        node.childNodes.forEach(processNode);
        wordML += `</w:r>`;
      } else if (tagName === 'u') {
        wordML += `<w:r><w:rPr><w:u w:val="single"/></w:rPr>`;
        node.childNodes.forEach(processNode);
        wordML += `</w:r>`;
      } else if (tagName === 'br') {
        wordML += `<w:br/>`;
      } else if (tagName === 'img') {
        const src = node.getAttribute('src');
        if (src && src.startsWith('data:image/')) {
          // Embed base64 image data directly
          const imgId = `rId${Math.floor(Math.random() * 1000000)}`; // Unique ID
          // This is a simplified example; actual embedding requires more complex relationships and binary data handling.
          // For full support, a dedicated library or backend service is usually required.
          wordML += `<w:p><w:r><w:drawing><wp:inline distT="0" distB="0" distL="0" distR="0">
                      <wp:extent cx="3600000" cy="1800000"/>
                      <wp:docPr id="${imgId}" name="Image${imgId}" descr="Image"/>
                      <a:graphic xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main">
                        <a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture">
                          <pic:pic xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture">
                            <pic:nvPicPr><pic:cNvPr id="0" name="Picture 1"/><pic:cNvPicPr/></pic:nvPicPr>
                            <pic:blipFill><a:blip r:embed="${imgId}" cstate="print"/></pic:blipFill>
                            <pic:spPr><a:xfrm><a:ext cx="3600000" cy="1800000"/></a:xfrm></pic:spPr>
                          </pic:pic>
                        </a:graphicData>
                      </a:graphic>
                    </wp:inline></w:drawing></w:r></w:p>`;
          // You'd also need to add this image as a relationship in word/_rels/document.xml.rels and embed it in word/media/
          // This part is significantly complex for a client-side pure JS solution without a specialized library.
        }
      } else {
        // For other tags, try to just process their children to keep content
        node.childNodes.forEach(processNode);
      }
    }
  };

  doc.body.childNodes.forEach(processNode);
  return wordML;
};


// Convert images to base64 for embedding
const convertImagesToBase64 = async (element) => {
  const images = element.querySelectorAll('img');
  const promises = Array.from(images).map(async (img) => {
    try {
      if (img.src.startsWith('data:')) {
        return; // Already base64
      }

      const canvas = document.createElement('canvas');
      const ctx = canvas.getContext('2d');
      const tempImg = new Image();

      return new Promise((resolve) => {
        tempImg.onload = () => {
          canvas.width = tempImg.naturalWidth;
          canvas.height = tempImg.naturalHeight;
          ctx.drawImage(tempImg, 0, 0);

          try {
            const dataURL = canvas.toDataURL('image/png');
            img.src = dataURL;
          } catch (e) {
            console.warn('Could not convert image to base64:', e);
          }
          resolve();
        };
        tempImg.onerror = () => resolve();
        tempImg.crossOrigin = 'anonymous';
        tempImg.src = img.src;
      });
    } catch (error) {
      console.warn('Error processing image:', error);
    }
  });

  await Promise.all(promises);
};

// Create styled HTML that preserves all formatting for Word
const createStyledHtmlForWord = async (container) => {
  const elements = container.querySelectorAll('*');

  elements.forEach(element => {
    const computedStyle = window.getComputedStyle(element);
    let inlineStyles = '';

    // Preserve important styling properties
    const importantStyles = [
      'font-family', 'font-size', 'font-weight', 'font-style',
      'color', 'background-color', 'text-align', 'text-decoration',
      'margin', 'padding', 'border', 'width', 'height',
      'line-height', 'letter-spacing', 'text-indent'
    ];

    importantStyles.forEach(prop => {
      const value = computedStyle.getPropertyValue(prop);
      if (value && value !== 'normal' && value !== 'auto') {
        inlineStyles += `${prop}: ${value}; `;
      }
    });

    if (inlineStyles) {
      element.style.cssText = inlineStyles + element.style.cssText;
    }
  });

  return container.innerHTML;
};


// Enhanced PDF Generation with Perfect Styling and Images
const generatePdfDocument = async (value = false) => {
  try {
    toaster.info('Generating PDF...');

    const jsPDF = (await import('jspdf')).jsPDF;
    const html2canvas = (await import('html2canvas')).default;

    let sourceContent = editorRef.value ? editorRef.value.innerHTML : documentContent.value;

    if (!sourceContent) {
      toaster.error('No content to generate PDF from');
      return;
    }

    // Create temporary container for HTML content
    // We will size this container to A4 dimensions (minus margins) to help html2canvas render it
    const tempContainer = document.createElement('div');
    tempContainer.style.cssText = `
      width: 210mm; /* A4 width */
      height: 347mm; /* A4 height - crucial for single page target */
      padding: 5mm; /* Consistent margins (e.g., 10mm all around) */
      box-sizing: border-box; /* Include padding in width/height */
      background-color: #ffffff;
      color: #000;
      font-family: 'Times New Roman', serif;
      font-size: 11pt; /* Keep a reasonable base font size, but it will scale */
      line-height: 1.3;
      word-wrap: break-word;
      overflow: hidden; /* Important: content that overflows this div will be clipped by html2canvas */
      display: flex; /* Use flexbox to encourage content compression/flow within the fixed height */
      flex-direction: column;
      justify-content: flex-start;
    `;

    // Content cleaning (keep as is)
    sourceContent = sourceContent
      .replace(/<h3[^>]*>.*?<\/h3>/gi, '') // Remove h3 headers
      .replace(/style="[^"]*background[^"]*"/gi, ''); // Remove background styles

    sourceContent = sourceContent.replace(/<p>\s*<\/p>/g, '').replace(/(<br>\s*){2,}/g, '<br>'); // Clean up empty paragraphs/excessive breaks

    tempContainer.innerHTML = sourceContent;
    document.body.appendChild(tempContainer);

    // Apply PDF-specific styling to the temporary container
    // This styling will affect how content renders *before* html2canvas takes the snapshot
    applyPdfStyling(tempContainer);

    // Ensure images are loaded and converted before canvas generation
    // Assuming these functions are defined elsewhere and work correctly
    await convertImagesToBase64(tempContainer);
    await waitForImagesToLoad(tempContainer);

    // Generate canvas from the tempContainer
    // html2canvas will render exactly what's visible within tempContainer's fixed 210x297mm dimensions
    const canvas = await html2canvas(tempContainer, {
      scale: 3, // High scale for clarity, even if text shrinks
      useCORS: true,
      allowTaint: true,
      backgroundColor: '#ffffff',
      // html2canvas will use the exact offsetWidth/offsetHeight of tempContainer (which is 210mm x 297mm)
      width: tempContainer.offsetWidth,
      height: tempContainer.offsetHeight,
      windowWidth: tempContainer.scrollWidth,
      windowHeight: tempContainer.scrollHeight,
      x: 0,
      y: 0
    });

    // Create PDF with exact A4 size
    const pdf = new jsPDF({
      orientation: 'portrait',
      unit: 'mm',
      format: 'a4',
      compress: true
    });

    const imgData = canvas.toDataURL('image/jpeg', 1.0); // Highest quality JPEG
    const pdfPageWidth = 210; // A4 width in mm
    const pdfPageHeight = 297; // A4 height in mm

    // Calculate the aspect ratio of the captured image
    const aspectRatio = canvas.width / canvas.height;

    let imgWidth;
    let imgHeight;

    // Determine if the image fits perfectly or needs scaling
    // We want the image to always take up the full width of the PDF page (210mm)
    imgWidth = pdfPageWidth;
    imgHeight = imgWidth / aspectRatio; // Calculate height based on full width and aspect ratio

    // If the calculated imgHeight is greater than the PDF page height (297mm),
    // it means the content rendered by html2canvas (even within the 297mm div)
    // was effectively "taller" than the page, or simply the aspect ratio is such
    // that fitting it to full width makes it taller.
    // In this case, we scale DOWN the height to fit the page, which will also scale the width.
    if (imgHeight > pdfPageHeight) {
      imgHeight = pdfPageHeight; // Set height to max page height
      imgWidth = imgHeight * aspectRatio; // Recalculate width based on scaled height
    }

    // Add the image to the PDF. It will now be scaled to fit perfectly on one page.
    // We can center it horizontally if it's not taking up the full width after scaling.
    const xOffset = (pdfPageWidth - imgWidth) / 2;
    const yOffset = (pdfPageHeight - imgHeight) / 2; // Center vertically as well

    pdf.addImage(imgData, 'JPEG', xOffset, yOffset, imgWidth, imgHeight);


    // Save PDF
    if (!value) {
      pdf.save(`document-${Date.now()}.pdf`);
    } 
      
    
    toaster.success('PDF generated successfully!');

    // Clean up
    document.body.removeChild(tempContainer);
    return pdf.output('blob'); // Return the PDF blob

  } catch (error) {
    console.error('PDF generation error:', error);
    toaster.error('Failed to generate PDF');
    throw error; // Re-throw to be caught by the save function
  }
};

// --- applyPdfStyling function (minimal changes to support the single-page goal) ---
const applyPdfStyling = (container) => {
  const style = document.createElement('style');
  style.textContent = `
    * {
      -webkit-print-color-adjust: exact !important;
      color-adjust: exact !important;
      print-color-adjust: exact !important;
      box-sizing: border-box; /* Ensure consistent box model */
    }

    body {
      margin: 0;
      padding: 0;
    }

    /* Reduce margins/padding on elements aggressively to maximize content area */
    h1, h2, h3, h4, h5, h6 {
      font-weight: bold !important;
      margin-top: 8px !important; /* Smaller top margin */
      margin-bottom: 4px !important; /* Smaller bottom margin */
      page-break-after: avoid !important;
      color: #000 !important;
    }

    h1 { font-size: 20px !important; } /* Smaller headings */
    h2 { font-size: 18px !important; }
    h3 { font-size: 16px !important; }
    h4 { font-size: 14px !important; }
    h5 { font-size: 12px !important; }
    h6 { font-size: 11px !important; }

    p {
      margin: 0 0 6px 0 !important; /* Very tight paragraph spacing */
      line-height: 1.2 !important; /* Tighter line height for paragraphs */
      color: #000 !important;
    }

    table {
      border-collapse: collapse !important;
      width: 100% !important;
      margin: 8px 0 !important; /* Smaller table margins */
      page-break-inside: avoid !important;
      border: 1px solid #d1d5db !important;
    }

    td, th {
      border: 1px solid #d1d5db !important;
      padding: 5px !important; /* Smaller cell padding */
      vertical-align: top !important;
      background-color: transparent !important;
      color: #000 !important;
    }

    th {
      background-color: #f0f0f0 !important;
      font-weight: bold !important;
      color: #000 !important;
    }

    img {
      max-width: 100% !important;
      height: auto !important;
      display: block !important;
      margin: 8px auto !important; /* Smaller image margins */
      page-break-inside: avoid !important;
    }

    ul, ol {
      margin: 6px 0 !important; /* Smaller list margins */
      padding-left: 10px !important; /* Less indentation */
      color: #000 !important;
    }

    li {
      margin-bottom: 1px !important; /* Very tight list item spacing */
      color: #000 !important;
    }

    strong, b { font-weight: bold !important; }
    em, i { font-style: italic !important; }
    u { text-decoration: underline !important; }

    .page-break {
      page-break-before: always !important;
    }

    a { text-decoration: underline !important; color: blue !important; }

    *:not(img) {
      background-color: transparent !important;
    }
  `;
  container.appendChild(style);
};


// Wait for all images to load
const waitForImagesToLoad = (container) => {
  const images = container.querySelectorAll('img');
  const promises = Array.from(images).map(img => {
    return new Promise((resolve) => {
      if (img.complete && img.naturalHeight !== 0) {
        resolve();
      } else {
        img.onload = () => resolve();
        img.onerror = () => resolve(); // Resolve even on error to prevent hanging
      }
    });
  });

  return Promise.all(promises);
};

// Fallback PDF generation using backend
const generatePdfDocumentFallback = async () => {
  try {
    const formData = new FormData();
    formData.append('html_content', documentContent.value);

    const response = await axios.post('/api/consultation/generate-pdf', formData, {
      responseType: 'blob'
    });

    const url = window.URL.createObjectURL(response.data);
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `document-${Date.now()}.pdf`);
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);

    toaster.success('PDF generated successfully!');
  } catch (error) {
    console.error('PDF generation error:', error);
    toaster.error('Failed to generate PDF');
  }
};

// Utility function to read file as array buffer
const readFileAsArrayBuffer = (file) => {
  return new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.onload = (e) => resolve(e.target.result);
    reader.onerror = reject;
    reader.readAsArrayBuffer(file);
  });
};

const preserveWordFormatting = (htmlContent) => {
  const parser = new DOMParser();
  const doc = parser.parseFromString(htmlContent, 'text/html');

  const processElements = (elements, processor) => {
    Array.from(elements).forEach(processor);
  };

  processElements(doc.querySelectorAll('table'), (table) => {
    table.style.borderCollapse = 'collapse';
    table.style.width = '100%';
    table.style.margin = '10px 0';
    table.style.border = '1px solid #d1d5db'; // Consistent border for tables
    table.setAttribute('data-word-table', 'true');
  });

  processElements(doc.querySelectorAll('td, th'), (cell) => {
    cell.style.border = '1px solid #d1d5db'; // Consistent border for cells
    if (!cell.style.padding) {
      cell.style.padding = '8px';
    }
    cell.setAttribute('data-word-cell', 'true');
  });

  processElements(doc.querySelectorAll('img'), (img) => {
    img.style.maxWidth = '100%';
    img.style.height = 'auto';
    img.style.display = 'block';
    img.style.margin = '10px auto';
    img.setAttribute('data-word-image', 'true');
  });

  processElements(doc.querySelectorAll('ul, ol'), (list) => {
    list.style.marginLeft = '20px';
    list.style.marginBottom = '15px';
    list.setAttribute('data-word-list', 'true');
  });

  processElements(doc.querySelectorAll('h1, h2, h3, h4, h5, h6'), (heading) => {
    const level = heading.tagName.toLowerCase();
    heading.setAttribute('data-word-heading', level);

    if (!heading.style.fontWeight) {
      heading.style.fontWeight = 'bold';
    }
    if (!heading.style.marginBottom) {
      heading.style.marginBottom = '10px';
    }
  });

  processElements(doc.querySelectorAll('p'), (para) => {
    para.setAttribute('data-word-paragraph', 'true');
    if (!para.style.marginBottom) {
      para.style.marginBottom = '10px';
    }
  });

  return doc.body.innerHTML;
};

const cleanDocxPreviewHtml = (html) => {
  // Remove docx-preview specific inline styles that might interfere with rendering
  html = html.replace(/style="[^"]*(?:position|top|left|width|height|z-index|min-height)[^"]*"/g, '');
  // Remove empty tags
  html = html.replace(/<[^\/>][^>]*>\s*<\/[^>]+>/g, '');
  // Remove docx-preview specific classes but keep others
  html = html.replace(/class="(?!docx-)[^"]*"/g, '');
  // Remove data attributes not explicitly marked to be preserved
  html = html.replace(/(?<!data-word-)data-[^=]+="[^"]*"/g, '');
  // Normalize paragraph breaks
  html = html.replace(/<\/p>\s*<p/g, '</p><p');
  // Trim excessive whitespace
  html = html.replace(/\s+/g, ' ');
  // Remove whitespace between tags
  html = html.replace(/>\s+</g, '><');

  return html;
};

const formatText = (command, value = null) => {
  if (!editorRef.value) return;

  document.execCommand(command, false, value);
  editorRef.value.focus();

  const rawContent = editorRef.value.innerHTML;
  const preservedContent = preserveWordFormatting(rawContent);
  documentContent.value = preservedContent;
  emit('update:previewContent', documentContent.value);
};

const insertList = (ordered = false) => {
  const command = ordered ? 'insertOrderedList' : 'insertUnorderedList';
  formatText(command);
};

const changeHeading = (level) => {
  if (level === 'p') {
    formatText('formatBlock', 'p');
  } else {
    formatText('formatBlock', `h${level}`);
  }
};

const insertLink = () => {
  const url = prompt('Enter URL:');
  if (url) {
    formatText('createLink', url);
  }
};

const toggleEditMode = () => {
  if (previewMode.value) {
    previewMode.value = false;
    nextTick(() => {
      if (editorRef.value) {
        editorRef.value.innerHTML = documentContent.value;
        editorRef.value.focus();
      }
    });
  } else {
    saveEditedContent();
  }
};

const saveEditedContent = () => {
  if (editorRef.value) {
    const rawContent = editorRef.value.innerHTML;
    const preservedContent = preserveWordFormatting(rawContent);
    documentContent.value = preservedContent;
    emit('update:previewContent', documentContent.value);
  }
  previewMode.value = true;
};

const refreshPreview = () => {
  emit('refresh');
};

const handleKeydown = (event) => {
  if (event.ctrlKey || event.metaKey) {
    switch (event.key) {
      case 'b':
        event.preventDefault();
        formatText('bold');
        break;
      case 'i':
        event.preventDefault();
        formatText('italic');
        break;
      case 'u':
        event.preventDefault();
        formatText('underline');
        break;
      case 's':
        event.preventDefault();
        saveEditedContent();
        break;
    }
  }
};

const handleEditorInput = () => {
  if (editorRef.value && !previewMode.value) {
    const rawContent = editorRef.value.innerHTML;
    const preservedContent = preserveWordFormatting(rawContent);
    documentContent.value = preservedContent;
    emit('update:previewContent', documentContent.value);
  }
};

onMounted(() => {
  documentContent.value = props.previewContent;
});

// New print method
const printDocument = async () => {
  toaster.info('Preparing document for printing...');
  try {
    // Generate the PDF in a blob
    const jsPDF = (await import('jspdf')).jsPDF;
    const html2canvas = (await import('html2canvas')).default;

    let sourceContent = editorRef.value ? editorRef.value.innerHTML : documentContent.value;

    if (!sourceContent) {
      toaster.error('No content to print');
      return;
    }

    const tempContainer = document.createElement('div');
    tempContainer.style.cssText = `
      width: 210mm; /* A4 width */
      height: 347mm; /* A4 height - crucial for single page target */
      padding: 5mm; /* Consistent margins (e.g., 10mm all around) */
      box-sizing: border-box; /* Include padding in width/height */
      background-color: #ffffff;
      color: #000;
      font-family: 'Times New Roman', serif;
      font-size: 11pt; /* Keep a reasonable base font size, but it will scale */
      line-height: 1.3;
      word-wrap: break-word;
      overflow: hidden; /* Important: content that overflows this div will be clipped by html2canvas */
      display: flex; /* Use flexbox to encourage content compression/flow within the fixed height */
      flex-direction: column;
      justify-content: flex-start;
    `;

    sourceContent = sourceContent
      .replace(/<h3[^>]*>.*?<\/h3>/gi, '')
      .replace(/style="[^"]*background[^"]*"/gi, '');

    sourceContent = sourceContent.replace(/<p>\s*<\/p>/g, '').replace(/(<br>\s*){2,}/g, '<br>');

    tempContainer.innerHTML = sourceContent;
    document.body.appendChild(tempContainer);
    applyPdfStyling(tempContainer); // Apply styling for consistent rendering
    await convertImagesToBase64(tempContainer);
    await waitForImagesToLoad(tempContainer);

    const canvas = await html2canvas(tempContainer, {
      scale: 3,
      useCORS: true,
      allowTaint: true,
      backgroundColor: '#ffffff',
      width: tempContainer.offsetWidth,
      height: tempContainer.offsetHeight,
      windowWidth: tempContainer.scrollWidth,
      windowHeight: tempContainer.scrollHeight,
      x: 0,
      y: 0
    });

    const pdf = new jsPDF({
      orientation: 'portrait',
      unit: 'mm',
      format: 'a4',
      compress: true
    });

    const imgData = canvas.toDataURL('image/jpeg', 1.0);
    const pdfPageWidth = 210;
    const pdfPageHeight = 297;
    const aspectRatio = canvas.width / canvas.height;

    let imgWidth = pdfPageWidth;
    let imgHeight = imgWidth / aspectRatio;

    if (imgHeight > pdfPageHeight) {
      imgHeight = pdfPageHeight;
      imgWidth = imgHeight * aspectRatio;
    }

    const xOffset = (pdfPageWidth - imgWidth) / 2;
    const yOffset = (pdfPageHeight - imgHeight) / 2;

    pdf.addImage(imgData, 'JPEG', xOffset, yOffset, imgWidth, imgHeight);

    // Generate a Data URL for the PDF and open it in a new window for printing
    const pdfUrl = pdf.output('bloburl');
    const printWindow = window.open(pdfUrl);

    if (printWindow) {
      printWindow.onload = () => {
        printWindow.print();
        toaster.success('Print dialog opened.');
        // Consider closing the window after a delay if auto-closing is desired
        // printWindow.onafterprint = () => printWindow.close();
      };
    } else {
      toaster.error('Could not open print window. Please allow pop-ups.');
    }

    document.body.removeChild(tempContainer);

  } catch (error) {
    console.error('Print error:', error);
    toaster.error('Failed to prepare document for printing.');
  }
};

// In DocumentPreview.vue, modify the savePdfToBackend function:

const savePdfToBackend = async () => {
  toaster.info('Saving PDF to backend...');
  try {
    const pdfBlob = await generatePdfDocument(true); // Generate the PDF and get its blob

    if (!pdfBlob) {
      toaster.error('Failed to generate PDF for saving.');
      return;
    }

    const formData = new FormData();
    const pdfFileName = `consultation_${props.patientId}_${Date.now()}.pdf`;
    formData.append('pdf_file', pdfBlob, pdfFileName);

    // Add template IDs and placeholder data to the form
    formData.append('template_ids', JSON.stringify(props.selectedTemplates));
    formData.append('placeholder_data', JSON.stringify(props.templateData));
    formData.append('patient_id', 1);

    // Post the PDF and trigger the event
    // const response = await axios.post(`/api/consultation/${props.patientId}/save-pdf`, formData, {
    const response = await axios.post(`/api/consultation/${1}/save-pdf`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    });

    if (response.data.success) {
      // The event will be triggered from the backend when the PDF is saved
      toaster.success('PDF saved successfully!');
      // You might want to emit an event to the parent component
      emit('documentSaved', response.data.path);
    } else {
      toaster.error(response.data.message || 'Failed to save PDF.');
    }
  } catch (error) {
    console.error('Error saving PDF:', error);
    toaster.error('An error occurred while saving the PDF.');
  }
};

// Expose methods for parent component
defineExpose({
  generateDocumentWithData,
  downloadGeneratedDocument,
  generateWordDocument,
  generatePdfDocument,
  printDocument,
  savePdfToBackend // Expose the new save function
});
</script>

<template>
  <div class="premium-document-editor">
    <div v-if="uploadProgress > 0 && uploadProgress < 100" class="upload-progress">
      <div class="progress-bar">
        <div class="progress-fill" :style="{ width: uploadProgress + '%' }"></div>
      </div>
      <div class="progress-text">{{ uploadProgress }}% - Processing document...</div>
    </div>

    <div class="premium-preview-main">
      <div class="premium-preview-header">
        <div class="premium-preview-title">
          <h3 class="title-text">
            Document Preview
            <span v-if="isTemplate" class="template-badge">Template</span>
          </h3>
        </div>

        <div class="premium-preview-actions">
          <button
            @click="toggleEditMode"
            class="premium-btn premium-edit-btn"
            :class="{ 'active': !previewMode }"
          >
            {{ previewMode ? '✏️ Edit' : '👁️ Preview' }}
          </button>

          <button @click="refreshPreview" class="premium-btn premium-refresh-btn">
            🔄 Refresh
          </button>

          <button
            @click="generatePdfDocument(false)"
            class="premium-btn premium-pdf-btn"
            :disabled="!documentContent"
          >
            📑 PDF
          </button>

          <button
            @click="printDocument"
            class="premium-btn premium-print-btn"
            :disabled="!documentContent"
          >
            🖨️ Print
          </button>

          <button
            @click="savePdfToBackend"
            class="premium-btn premium-save-to-backend-btn"
            :disabled="!documentContent"
          >
            💾 Save PDF
          </button>

          <button
            v-if="isTemplate"
            @click="downloadGeneratedDocument()"
            class="premium-download-btn"
            :disabled="!templateFile"
          >
            ⬇️ Download
          </button>
        </div>
      </div>

      <div v-if="!previewMode" class="premium-editor-toolbar">
        <div class="toolbar-group">
          <button @click="formatText('bold')" class="toolbar-btn" title="Bold">
            <strong>B</strong>
          </button>
          <button @click="formatText('italic')" class="toolbar-btn" title="Italic">
            <em>I</em>
          </button>
          <button @click="formatText('underline')" class="toolbar-btn" title="Underline">
            <u>U</u>
          </button>
        </div>

        <div class="toolbar-group">
          <select @change="changeHeading($event.target.value)" class="heading-select">
            <option value="p">Paragraph</option>
            <option value="1">Heading 1</option>
            <option value="2">Heading 2</option>
            <option value="3">Heading 3</option>
            <option value="4">Heading 4</option>
          </select>
        </div>

        <div class="toolbar-group">
          <button @click="insertList(false)" class="toolbar-btn" title="Bullet List">
            • List
          </button>
          <button @click="insertList(true)" class="toolbar-btn" title="Numbered List">
            1. List
          </button>
        </div>

        <div class="toolbar-group">
          <button @click="insertLink()" class="toolbar-btn" title="Insert Link">
            🔗 Link
          </button>
        </div>

        <div class="toolbar-group">
          <button @click="saveEditedContent()" class="toolbar-btn save-btn" title="Save (Ctrl+S)">
            💾 Save
          </button>
        </div>
      </div>

      <div class="premium-document-preview-container">
        <div v-if="loading" class="loading-state">
          <div class="loading-spinner"></div>
          <p>Loading document preview...</p>
        </div>

        <div v-else-if="!documentContent" class="empty-state">
          <div class="empty-icon">📄</div>
          <h3>No Document Loaded</h3>
          <p>Upload a DOCX file or add content to get started</p>
        </div>

        <div v-else class="document-wrapper">
          <div
            v-if="previewMode"
            class="document-preview"
            v-html="documentContent"
          ></div>

          <div
            v-else
            ref="editorRef"
            class="document-editor"
            contenteditable="true"
            @input="handleEditorInput"
            @keydown="handleKeydown"
            spellcheck="false"
          ></div>
        </div>
      </div>
    </div>
  </div>
</template>
<style scoped>
.premium-document-editor {
  width: 100%;
  max-width: 100%;
  height: 500px;
  overflow: hidden;

}

.upload-progress {
  padding: 1rem 1.5rem;
  background-color: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
  margin-bottom: 1rem;
}

.progress-bar {
  width: 100%;
  height: 8px;
  background-color: #e2e8f0;
  border-radius: 4px;
  overflow: hidden;
  margin-bottom: 0.5rem;
}

.progress-fill {
  height: 100%;
  background: linear-gradient(90deg, #3b82f6, #10b981);
  transition: width 0.3s ease;
}

.progress-text {
  font-size: 0.875rem;
  color: #64748b;
  text-align: center;
}

.premium-preview-main {
  background: white;
  border-radius: 12px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
  overflow : hidden;
}

.premium-preview-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 1.5rem;
  background-color: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
  flex-wrap: wrap;
  gap: 1rem;
}

.premium-preview-title .title-text {
  font-size: 1.1rem;
  font-weight: 600;
  color: #334155;
  margin: 0;
  display: flex;
  align-items: center;
}

.template-badge {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  font-size: 0.75rem;
  padding: 0.25rem 0.5rem;
  border-radius: 12px;
  margin-left: 0.5rem;
  font-weight: 500;
}

.premium-preview-actions {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
  overflow: hidden;

}

.premium-upload-btn, .premium-download-btn {
  background-color: #10b981;
  color: white;
  border: none;
  padding: 0.5rem 1rem;
  border-radius: 6px;
  font-size: 0.875rem;
  transition: all 0.3s ease;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  text-decoration: none;
}

.premium-upload-btn:hover, .premium-download-btn:hover {
  background-color: #059669;
  transform: translateY(-1px);
}

.premium-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  border: none;
  border-radius: 6px;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
}

.premium-edit-btn {
  background-color: #8b5cf6;
  color: white;
}

.premium-edit-btn:hover, .premium-edit-btn.active {
  background-color: #7c3aed;
}

.premium-refresh-btn {
  background-color: #6b7280;
  color: white;
}

.premium-refresh-btn:hover {
  background-color: #4b5563;
}

.premium-word-btn {
  background-color: #2b579a;
  color: white;
}

.premium-word-btn:hover {
  background-color: #1e3f6f;
}

.premium-pdf-btn {
  background-color: #dc2626;
  color: white;
}

.premium-pdf-btn:hover {
  background-color: #b91c1c;
}

.premium-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
}

.premium-editor-toolbar {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 0.75rem 1.5rem;
  background-color: #f1f5f9;
  border-bottom: 1px solid #e2e8f0;
  flex-wrap: wrap;
}

.toolbar-group {
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.toolbar-btn {
  padding: 0.375rem 0.75rem;
  border: 1px solid #d1d5db;
  background: white;
  border-radius: 4px;
  cursor: pointer;
  font-size: 0.875rem;
  transition: all 0.2s ease;
}

.toolbar-btn:hover {
  background-color: #f3f4f6;
  border-color: #9ca3af;
}

.save-btn {
  background-color: #10b981;
  color: white;
  border-color: #10b981;
}

.save-btn:hover {
  background-color: #059669;
}

.heading-select {
  padding: 0.375rem 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 4px;
  background: white;
  font-size: 0.875rem;
}

.premium-document-preview-container {
  padding: 2rem;
  max-height: 70vh;
  overflow-y: auto;
  background-color: #fff;
}

.loading-state, .empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem;
  text-align: center;
  color: #64748b;
}

.loading-spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #e2e8f0;
  border-top: 4px solid #3b82f6;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 1rem;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.empty-icon {
  font-size: 3rem;
  margin-bottom: 1rem;
  opacity: 0.5;
}

.document-wrapper {
  min-height: 400px;
}

.document-preview, .document-editor {
  font-family: 'Times New Roman', serif;
  font-size: 12pt;
  line-height: 1.6;
  color: #000;
  word-wrap: break-word;
}

.document-editor {
  border: 2px dashed #e2e8f0;
  border-radius: 8px;
  padding: 1rem;
  min-height: 400px;
  outline: none;
}

.document-editor:focus {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Document styling for both preview and editor */
.document-preview :deep(h1),
.document-preview :deep(h2),
.document-preview :deep(h3),
.document-preview :deep(h4),
.document-preview :deep(h5),
.document-preview :deep(h6),
.document-editor h1,
.document-editor h2,
.document-editor h3,
.document-editor h4,
.document-editor h5,
.document-editor h6 {
  font-weight: bold;
  margin-top: 1.5rem;
  margin-bottom: 0.75rem;
  color: #1f2937;
}

.document-preview :deep(h1), .document-editor h1 { font-size: 24px; }
.document-preview :deep(h2), .document-editor h2 { font-size: 20px; }
.document-preview :deep(h3), .document-editor h3 { font-size: 18px; }
.document-preview :deep(h4), .document-editor h4 { font-size: 16px; }

.document-preview :deep(p),
.document-editor p {
  margin: 0 0 1rem 0;
  text-align: justify;
}

.document-preview :deep(table),
.document-editor table {
  border-collapse: collapse;
  width: 100%;
  margin: 1rem 0;
  border: 1px solid #d1d5db; /* Consistent border */
}

.document-preview :deep(td),
.document-preview :deep(th),
.document-editor td,
.document-editor th {
  border: 1px solid #d1d5db; /* Consistent border */
  padding: 0.75rem;
  text-align: left;
  vertical-align: top;
}

.document-preview :deep(th),
.document-editor th {
  background-color: #f9fafb;
  font-weight: bold;
}

.document-preview :deep(img),
.document-editor img {
  max-width: 100%;
  height: auto;
  display: block;
  margin: 1rem auto;
  border-radius: 4px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.document-preview :deep(ul),
.document-preview :deep(ol),
.document-editor ul,
.document-editor ol {
  margin: 1rem 0;
  padding-left: 2rem;
}

.document-preview :deep(li),
.document-editor li {
  margin-bottom: 0.5rem;
}

.document-preview :deep(blockquote),
.document-editor blockquote {
  margin: 1rem 0;
  padding: 1rem;
  background-color: #f8fafc;
  border-left: 4px solid #3b82f6;
  font-style: italic;
}

.document-preview :deep(code),
.document-editor code {
  background-color: #f1f5f9;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-family: 'Courier New', monospace;
  font-size: 0.9em;
}

.document-preview :deep(pre),
.document-editor pre {
  background-color: #f1f5f9;
  padding: 1rem;
  border-radius: 8px;
  overflow-x: auto;
  margin: 1rem 0;
}

.document-preview :deep(pre code),
.document-editor pre code {
  background: none;
  padding: 0;
}

/* Responsive design */
@media (max-width: 768px) {
  .premium-preview-header {
    flex-direction: column;
    align-items: stretch;
  }
  
  .premium-preview-actions {
    justify-content: center;
  }
  
  .premium-editor-toolbar {
    flex-direction: column;
    align-items: stretch;
  }
  
  .toolbar-group {
    justify-content: center;
  }
  
  .premium-document-preview-container {
    padding: 1rem;
  }
}

/* Print styles */
@media print {
  .premium-preview-header,
  .premium-editor-toolbar {
    display: none;
  }
  
  .premium-document-preview-container {
    max-height: none;
    overflow: visible;
    padding: 0;
  }
  
  .document-preview,
  .document-editor {
    font-size: 12pt;
    line-height: 1.5;
  }
}
</style>