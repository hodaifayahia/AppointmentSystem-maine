import axios from 'axios';

export function useDocumentActions(toaster) {

  const downloadDocument = (blob, filename) => {
    if (!blob) {
      toaster.error('No document to download.');
      return;
    }
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
    toaster.success('Document downloaded successfully!');
  };

  const printDocument = (pdfBlob) => {
    if (!pdfBlob) {
      toaster.error('No PDF to print.');
      return;
    }
    toaster.info('Preparing document for printing...');
    try {
      const pdfUrl = URL.createObjectURL(pdfBlob);
      const printWindow = window.open(pdfUrl);

      if (printWindow) {
        printWindow.onload = () => {
          printWindow.print();
          // Optional: close window after print dialog is handled by user
          // printWindow.onafterprint = () => printWindow.close();
          toaster.success('Print dialog opened.');
        };
      } else {
        toaster.error('Could not open print window. Please allow pop-ups.');
      }
    } catch (error) {
      console.error('Print error:', error);
      toaster.error('Failed to prepare document for printing.');
    }
  };
const savePdfToBackend = async (pdfBlob, patientId, templateIds, placeholderData, appointmentId) => {
  toaster.info('Saving document...');

  try {
    const formData = new FormData();
    formData.append('pdf_file', pdfBlob, 'document.pdf');
    formData.append('patient_id', patientId);
    formData.append('template_ids', JSON.stringify(templateIds));
    formData.append('placeholder_data', JSON.stringify(placeholderData));
    formData.append('appointment_id', appointmentId);

    // Change to multipart/form-data to handle file upload
    const response = await axios.post(`/api/consultation/${patientId}/save-pdf`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    });

    if (response.data.success) {
      toaster.success('Document saved successfully!');
      return response.data.path;
    } else {
      toaster.error(response.data.message || 'Failed to save document.');
      return null;
    }
  } catch (error) {
    console.error('Error saving document:', error);
    toaster.error('An error occurred while saving the document.');
    return null;
  }
};
  return {
    downloadDocument,
    printDocument,
    savePdfToBackend,
  };
}

// Example usage in your Vue component
// In your Vue component where the save button is handled
const handleSave = async () => {
  // First generate the PDF blob (you need to implement this based on your PDF generation logic)
  const pdfBlob = await generatePdf(documentContent.value); // Implement this function
  
  const result = await savePdfToBackend(
    pdfBlob,
    props.patientId,
    props.selectedTemplates,
    props.templateData,
    props.appointmentId
  );
  
  if (result) {
    emit('documentSaved', result);
  }
};