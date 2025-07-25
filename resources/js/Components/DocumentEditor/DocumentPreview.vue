<script setup>
import { ref, watch, onMounted, nextTick } from 'vue';

const props = defineProps({
  content: {
    type: String,
    default: ''
  },
  previewMode: {
    type: Boolean,
    default: true
  },
  editorRef: { // Pass a ref from parent to expose the contenteditable div
    type: Object, // HTMLElement ref
    default: null
  }
});

const emit = defineEmits(['update:content', 'editor-input', 'editor-keydown']);

const localContent = ref(props.content);

// Watch for external content changes to update local content
watch(() => props.content, (newValue) => {
  if (props.previewMode) {
    localContent.value = newValue;
  }
});

// Watch for previewMode changes to sync content to editor on switch
watch(() => props.previewMode, (newMode) => {
  if (!newMode && props.editorRef && props.editorRef.value) {
    props.editorRef.value.innerHTML = localContent.value;
    props.editorRef.value.focus();
  }
});

// Handle input from the contenteditable div
const handleInput = () => {
  if (props.editorRef && props.editorRef.value) {
    emit('update:content', props.editorRef.value.innerHTML);
    emit('editor-input', props.editorRef.value.innerHTML); // Specific event for parent
  }
};

// Handle keydown events from the contenteditable div
const handleKeydown = (event) => {
  emit('editor-keydown', event);
};

onMounted(() => {
  localContent.value = props.content;
  // If starting in edit mode, ensure content is set
  if (!props.previewMode && props.editorRef && props.editorRef.value) {
    props.editorRef.value.innerHTML = localContent.value;
  }
});
</script>

<template>
  <div class="document-wrapper">
    <div
      v-if="previewMode"
      class="document-preview"
      v-html="localContent"
    ></div>

    <div
      v-else
      :ref="editorRef"
      class="document-editor"
      contenteditable="true"
      @input="handleInput"
      @keydown="handleKeydown"
      spellcheck="false"
    ></div>
  </div>
</template>

<style scoped>
/* These styles were moved from DocumentEditor.vue */
.document-wrapper {
  background-color: #fff;
  padding: 20px;
  border: 1px solid #eee;
  box-shadow: 0 2px 8px rgba(0,0,0,0.05);
  min-height: 300px;
}

/* Specific styles for docx-preview content.
   These styles target elements generated by docx-preview.
   You might need to adjust these based on your actual docx files. */
.document-preview :deep(.docx-wrapper) {
  padding: 0 !important; /* Override default docx-preview padding */
  margin: 0 auto !important; /* Center the content if it's smaller than wrapper */
  max-width: 794px; /* A4 width at 96 DPI, roughly */
  box-shadow: none; /* Remove docx-preview's default shadow */
  border: none; /* Remove docx-preview's default border */
}

.document-preview :deep(p) {
  margin-bottom: 0.5em; /* Default paragraph spacing */
  line-height: 1.5;
}

.document-preview :deep(h1),
.document-preview :deep(h2),
.document-preview :deep(h3),
.document-preview :deep(h4),
.document-preview :deep(h5),
.document-preview :deep(h6) {
  margin-top: 1em;
  margin-bottom: 0.5em;
  font-weight: bold;
}

.document-preview :deep(table) {
  border-collapse: collapse;
  width: 100%;
  margin: 1em 0;
  border: 1px solid #d1d5db; /* Default table border */
}

.document-preview :deep(td),
.document-preview :deep(th) {
  border: 1px solid #d1d5db; /* Default cell border */
  padding: 8px;
  vertical-align: top;
}

.document-preview :deep(th) {
  background-color: #f7f7f7;
  font-weight: bold;
}

.document-preview :deep(img) {
  max-width: 100%;
  height: auto;
  display: block;
  margin: 1em auto;
}

.document-preview :deep(ul),
.document-preview :deep(ol) {
  margin: 1em 0;
  padding-left: 20px;
}

.document-preview :deep(li) {
  margin-bottom: 0.5em;
}

.document-preview :deep(strong),
.document-preview :deep(b) {
  font-weight: bold;
}

.document-preview :deep(em),
.document-preview :deep(i) {
  font-style: italic;
}

.document-preview :deep(u) {
  text-decoration: underline;
}

/* Specific styling for the contenteditable area */
.document-editor {
  border: 1px solid #ddd;
  padding: 20px;
  min-height: 300px;
  background-color: #ffffff;
  font-family: 'Times New Roman', serif;
  font-size: 11pt;
  line-height: 1.5;
  box-shadow: inset 0 1px 3px rgba(0,0,0,0.1);
  overflow-y: auto;
  cursor: text;
}

/* Ensure images within contenteditable are draggable and resizable (browser native) */
.document-editor :deep(img) {
  max-width: 100%;
  height: auto;
  display: block;
  margin: 1em auto;
  cursor: grab;
}

/* Basic styling for when in edit mode */
.document-editor:focus {
  outline: 2px solid #007bff;
  outline-offset: -1px;
}
</style>