import { ref } from 'vue';
// Dynamic imports for heavy libraries
// For docx-preview, install: npm install docx-preview
// For jspdf, install: npm install jspdf
// For html2canvas, install: npm install html2canvas
// For pizzip & docxtemplater: npm install pizzip docxtemplater

export function useDocumentConversion(toaster) { // Accept toaster as a dependency

  // Utility to read file as array buffer
  const readFileAsArrayBuffer = (file) => {
    return new Promise((resolve, reject) => {
      const reader = new FileReader();
      reader.onload = (e) => resolve(e.target.result);
      reader.onerror = reject;
      reader.readAsArrayBuffer(file);
    });
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
      return `<div class="document-content" contenteditable="true">${renderedHTML}</div>`;

    } catch (error) {
      console.error('Docx rendering error:', error);
      toaster.error('Failed to render DOCX to HTML.');
      return '';
    }
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
        console.warn('Error processing image for base64 conversion:', error);
      }
    });

    await Promise.all(promises);
  };

  // Create styled HTML that preserves all formatting for Word (client-side attempt)
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
        'line-height', 'letter-spacing', 'text-indent', 'list-style-type'
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


  // Helper to convert HTML to simplified WordprocessingML
  // NOTE: This is a highly simplified conversion. For robust DOCX generation,
  // consider a dedicated backend service or a more advanced client-side library.
  const convertHtmlToWordprocessingML = (htmlString) => {
    const doc = new DOMParser().parseFromString(htmlString, 'text/html');
    let wordML = '';

    const processNode = (node) => {
      if (node.nodeType === Node.TEXT_NODE) {
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
          if (styleAttr.includes('font-weight: bold')) styleXml += '<w:b/>';
          if (styleAttr.includes('font-style: italic')) styleXml += '<w:i/>';
          if (styleAttr.includes('text-decoration: underline')) styleXml += '<w:u w:val="single"/>';
          if (styleAttr.includes('text-align: center')) styleXml += '<w:jc w:val="center"/>';
          if (styleAttr.includes('text-align: right')) styleXml += '<w:jc w:val="right"/>';
          if (styleAttr.includes('text-align: justify')) styleXml += '<w:jc w:val="both"/>';
          // Add more style conversions as needed (font size, color, etc.)
        }

        if (['p', 'div'].includes(tagName)) {
          // If a paragraph, add a run for text and properties
          wordML += `<w:p><w:r><w:rPr>${styleXml}</w:rPr>`;
          node.childNodes.forEach(processNode);
          wordML += `</w:r></w:p>`;
        } else if (['h1', 'h2', 'h3', 'h4', 'h5', 'h6'].includes(tagName)) {
          const headingLevel = tagName.charAt(1);
          wordML += `<w:p><w:pPr><w:pStyle w:val="Heading${headingLevel}"/></w:pPr><w:r><w:rPr>${styleXml}</w:rPr>`;
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
            // This is a placeholder for image embedding.
            // Full image embedding requires adding the image data to word/media and updating relationships.
            // This is beyond a simple HTML to WordprocessingML conversion without a dedicated library.
            // For now, it just adds a placeholder.
            wordML += `<w:p><w:r><w:t>[IMAGE]</w:t></w:r></w:p>`;
          }
        } else if (tagName === 'ul' || tagName === 'ol') {
          wordML += `<w:p><w:r><w:t>List:</w:t></w:r></w:p>`; // Simplified list representation
          node.childNodes.forEach(processNode);
        } else if (tagName === 'li') {
          wordML += `<w:p><w:r><w:t>- </w:t>`;
          node.childNodes.forEach(processNode);
          wordML += `</w:r></w:p>`;
        } else if (tagName === 'table') {
          // Simplified table representation - a real table requires w:tbl, w:tr, w:tc
          wordML += `<w:p><w:r><w:t>[TABLE]</w:t></w:r></w:p>`;
          node.childNodes.forEach(processNode);
        } else {
          // For other tags, try to just process their children to keep content
          node.childNodes.forEach(processNode);
        }
      }
    };

    doc.body.childNodes.forEach(processNode);
    return wordML;
  };


  const generateWordDocument = async (htmlContent) => {
    toaster.info('Generating Word document...');
    try {
      const PizZip = (await import('pizzip')).default;
      const Docxtemplater = (await import('docxtemplater')).default;

      // Create a temporary container to apply styles and process HTML elements
      const tempDiv = document.createElement('div');
      tempDiv.innerHTML = htmlContent;

      await convertImagesToBase64(tempDiv);
      const styledHtml = await createStyledHtmlForWord(tempDiv);

      // Minimal DOCX structure to insert the styled HTML
      // Note: This is a very basic structure. Complex HTML will not translate perfectly.
      const docxXml = `<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <w:body>
    ${convertHtmlToWordprocessingML(styledHtml)}
  </w:body>
</w:document>`;

      const zip = new PizZip();
      zip.file('word/document.xml', docxXml);
      // Add necessary boilerplate XML files for a valid DOCX
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
      zip.file('word/_rels/document.xml.rels', `<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
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
      return output;
    } catch (error) {
      console.error('Word generation error:', error);
      toaster.error('Failed to generate Word document');
      throw error;
    }
  };

  // Apply PDF-specific styling to the temporary container
  const applyPdfStyling = (container) => {
    const style = document.createElement('style');
    style.textContent = `
      * {
        -webkit-print-color-adjust: exact !important;
        color-adjust: exact !important;
        print-color-adjust: exact !important;
        box-sizing: border-box;
      }

      body {
        margin: 0;
        padding: 0;
      }

      h1, h2, h3, h4, h5, h6 {
        font-weight: bold !important;
        margin-top: 8px !important;
        margin-bottom: 4px !important;
        page-break-after: avoid !important;
        color: #000 !important;
      }

      h1 { font-size: 20px !important; }
      h2 { font-size: 18px !important; }
      h3 { font-size: 16px !important; }
      h4 { font-size: 14px !important; }
      h5 { font-size: 12px !important; }
      h6 { font-size: 11px !important; }

      p {
        margin: 0 0 6px 0 !important;
        line-height: 1.2 !important;
        color: #000 !important;
      }

      table {
        border-collapse: collapse !important;
        width: 100% !important;
        margin: 8px 0 !important;
        page-break-inside: avoid !important;
        border: 1px solid #d1d5db !important;
      }

      td, th {
        border: 1px solid #d1d5db !important;
        padding: 5px !important;
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
        margin: 8px auto !important;
        page-break-inside: avoid !important;
      }

      ul, ol {
        margin: 6px 0 !important;
        padding-left: 10px !important;
        color: #000 !important;
      }

      li {
        margin-bottom: 1px !important;
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

  const generatePdfDocument = async (htmlContent) => {
    toaster.info('Generating PDF...');

    try {
      const jsPDF = (await import('jspdf')).jsPDF;
      const html2canvas = (await import('html2canvas')).default;

      // Create temporary container for HTML content
      const tempContainer = document.createElement('div');
      tempContainer.style.cssText = `
        width: 210mm; /* A4 width */
        height: 297mm; /* A4 height */
        padding: 5mm; /* Consistent margins (e.g., 10mm all around) */
        box-sizing: border-box; /* Include padding in width/height */
        background-color: #ffffff;
        color: #000;
        font-family: 'Times New Roman', serif;
        font-size: 11pt;
        line-height: 1.3;
        word-wrap: break-word;
        overflow: hidden; /* Important: content that overflows this div will be clipped by html2canvas */
        display: flex;
        flex-direction: column;
        justify-content: flex-start;
      `;

      // Content cleaning (keep as is)
      htmlContent = htmlContent
        .replace(/<h3[^>]*>.*?<\/h3>/gi, '') // Remove h3 headers (consider if this is desired for PDF)
        .replace(/style="[^"]*background[^"]*"/gi, ''); // Remove background styles

      htmlContent = htmlContent.replace(/<p>\s*<\/p>/g, '').replace(/(<br>\s*){2,}/g, '<br>');

      tempContainer.innerHTML = htmlContent;
      document.body.appendChild(tempContainer);

      // Apply PDF-specific styling
      applyPdfStyling(tempContainer);

      // Ensure images are loaded and converted before canvas generation
      await convertImagesToBase64(tempContainer);
      await waitForImagesToLoad(tempContainer);

      const canvas = await html2canvas(tempContainer, {
        scale: 3, // High scale for clarity, even if text shrinks
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

      let imgWidth = pdfPageWidth;
      let imgHeight = imgWidth / aspectRatio;

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
      const xOffset = (pdfPageWidth - imgWidth) / 2;
      const yOffset = (pdfPageHeight - imgHeight) / 2; // Center vertically as well

      pdf.addImage(imgData, 'JPEG', xOffset, yOffset, imgWidth, imgHeight);

      // Clean up
      document.body.removeChild(tempContainer);
      return pdf.output('blob'); // Return the PDF blob
    } catch (error) {
      console.error('PDF generation error:', error);
      toaster.error('Failed to generate PDF');
      throw error;
    }
  };


  return {
    renderDocxToHtml,
    generateWordDocument,
    generatePdfDocument,
    cleanDocxPreviewHtml,
    preserveWordFormatting,
    convertImagesToBase64,
    waitForImagesToLoad,
  };
}