// For pizzip & docxtemplater: npm install pizzip docxtemplater
import { readFileAsArrayBuffer } from '../utils/fileReaders'; // Utility for reading files

export function useTemplateProcessing(toaster, renderDocxToHtml, cleanDocxPreviewHtml, preserveWordFormatting) {

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
        /{[^}]+}/g,      // e.g., {my_placeholder}
        /\$\{[^}]+\}/g,  // e.g., ${my_placeholder} (common in some templating engines)
        /\[\[.*?\]\]/g,  // e.g., [[my_placeholder]] (another common pattern)
      ];

      return templatePatterns.some(pattern => pattern.test(xmlContent));
    } catch (error) {
      console.log('Template detection failed, treating as regular document:', error);
      return false;
    }
  };

  const extractPlaceholders = (doc) => {
    const placeholders = new Set();

    try {
      doc.setData({}); // Try rendering with empty data to expose missing tags
      doc.render();
    } catch (error) {
      const errorMessage = error.message || '';
      // Look for error messages specific to missing tags from docxtemplater
      const matches = errorMessage.match(/tag "([^"]+)"/g);
      if (matches) {
        matches.forEach(match => {
          const placeholder = match.replace(/tag "|"/g, '');
          placeholders.add(placeholder);
        });
      }
      // You might also try parsing the XML directly for patterns if docxtemplater doesn't catch all
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
        sampleData[placeholder] = new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
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
      } else if (lowerPlaceholder.includes('description') || lowerPlaceholder.includes('details')) {
        sampleData[placeholder] = 'This is a sample description for the document.';
      }
      else {
        sampleData[placeholder] = `[${placeholder}]`;
      }
    });

    return sampleData;
  };

  const processTemplateFile = async (file, dataToUse = {}) => {
    try {
      const PizZip = (await import('pizzip')).default;
      const Docxtemplater = (await import('docxtemplater')).default;

      // Initial progress can be emitted by the Uploader itself
      // toaster.info('Processing template...');

      const content = await readFileAsArrayBuffer(file);
      const zip = new PizZip(content);

      const doc = new Docxtemplater(zip, {
        paragraphLoop: true,
        linebreaks: true,
      });

      const placeholders = extractPlaceholders(doc);
      const effectiveData = Object.keys(dataToUse).length > 0 ? dataToUse : generateSampleData(placeholders);

      doc.setData(effectiveData);
      doc.render(); // This might throw if data doesn't match placeholders perfectly

      const renderedBuffer = doc.getZip().generate({ type: 'arraybuffer' });
      const renderedBlob = new Blob([renderedBuffer], { type: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' });

      // Use the injected renderDocxToHtml for preview
      const renderedHtml = await renderDocxToHtml(renderedBlob);

      toaster.success('Template processed and preview generated!');
      return { renderedHtml, placeholders, renderedData: effectiveData };

    } catch (error) {
      console.error('Template processing error:', error);
      toaster.error('Failed to process template. Falling back to regular document preview if possible.');
      // Attempt to render as a regular docx if template processing fails
      const docxBlob = new Blob([await file.arrayBuffer()], { type: file.type });
      const renderedHtml = await renderDocxToHtml(docxBlob);
      return { renderedHtml, placeholders: [], renderedData: {} };
    }
  };

  const generateDocumentWithData = async (templateFile, newData) => {
    if (!templateFile) {
      toaster.error('No template file available for generation.');
      return null;
    }

    try {
      const PizZip = (await import('pizzip')).default;
      const Docxtemplater = (await import('docxtemplater')).default;

      const content = await readFileAsArrayBuffer(templateFile);
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

      toaster.success('Document generated from template successfully!');
      return blob;

    } catch (error) {
      console.error('Document generation error with data:', error);
      toaster.error('Failed to generate document from template with provided data. Check data consistency.');
      throw error; // Re-throw for parent to handle (e.g., show SweetAlert)
    }
  };

  return {
    detectTemplateFile,
    extractPlaceholders,
    generateSampleData,
    processTemplateFile,
    generateDocumentWithData,
  };
}