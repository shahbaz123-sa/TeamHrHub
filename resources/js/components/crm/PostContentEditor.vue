<script setup>
import { BulletList } from '@tiptap/extension-bullet-list'
import { Heading } from '@tiptap/extension-heading'
import Image from '@tiptap/extension-image'
import { Placeholder } from '@tiptap/extension-placeholder'
import { TableKit } from '@tiptap/extension-table'
import { TextAlign } from '@tiptap/extension-text-align'
import { Underline } from '@tiptap/extension-underline'
import { StarterKit } from '@tiptap/starter-kit'
import {
  EditorContent,
  useEditor,
} from '@tiptap/vue-3'
import { useFileDialog } from '@vueuse/core'

const props = defineProps({
  modelValue: {
    type: String,
    required: true,
  },
  placeholder: {
    type: String,
    required: false,
  },
})

const emit = defineEmits(['update:modelValue', 'addAdditionalImage'])

const editorRef = ref()

const editor = useEditor({
  content: props.modelValue,
  extensions: [
    StarterKit,
    BulletList,
    Heading,
    TableKit.configure({
      resizable: true,
      cellMinWidth: 100,
      HTMLAttributes: {
        class: 'content-table',
      },
    }),
    Image.configure({
      resize: {
        enabled: true,
        directions: ['top', 'bottom', 'left', 'right'],
        minWidth: 50,
        minHeight: 50,
        alwaysPreserveAspectRatio: false,
      }
    }),
    TextAlign.configure({
      types: [
        'heading',
        'paragraph',
      ],
    }),
    Placeholder.configure({ placeholder: props.placeholder ?? 'Write something here...' }),
    Underline,
  ],
  onUpdate() {
    if (!editor.value)
      return
    emit('update:modelValue', editor.value.getHTML())
  },
})

const { open, onChange } = useFileDialog({ accept: 'image/*', multiple: false })

onChange(selectedFiles => {
  if(selectedFiles?.length > 0) {
    const file = selectedFiles[0]
    if(file) {
      const url = useObjectUrl(file).value ?? '';
      addAdditionalImage(file, url)
    }
  }
})

const addAdditionalImage = (file, url) => {
  if (url) {
    editor.value
      ?.chain()
      .focus()
      .setImage({ src: url })
      .setTextSelection(editor.value.state.selection.to + 1)
      .setHardBreak()
      .run();

    emit('addAdditionalImage', file, url)
  }
}

watch(() => props.modelValue, () => {
  const isSame = editor.value?.getHTML() === props.modelValue
  if (isSame)
    return
  editor.value?.commands.setContent(props.modelValue)
})
</script>

<template>
  <div class="pa-6 productDescriptionEditor">
    <!-- buttons -->
    <div
      v-if="editor"
      class="d-flex gap-1 flex-wrap align-center"
    >
      <VBtn
        size="small"
        icon
        rounded
        :variant="editor.isActive('heading', {level: 1}) ? 'tonal' : 'text'"
        :color="editor.isActive('heading', {level: 1}) ? 'primary' : 'default'"
        @click="editor.chain().focus().toggleHeading({level: 1}).run()"
      >
        <VIcon
          icon="tabler-h-1"
          class="font-weight-medium"
        />
      </VBtn>
      
      <VBtn
        size="small"
        icon
        rounded
        :variant="editor.isActive('heading', {level: 2}) ? 'tonal' : 'text'"
        :color="editor.isActive('heading', {level: 2}) ? 'primary' : 'default'"
        @click="editor.chain().focus().toggleHeading({level: 2}).run()"
      >
        <VIcon
          icon="tabler-h-2"
          class="font-weight-medium"
        />
      </VBtn>
      
      <VBtn
        size="small"
        icon
        rounded
        :variant="editor.isActive('heading', {level: 3}) ? 'tonal' : 'text'"
        :color="editor.isActive('heading', {level: 3}) ? 'primary' : 'default'"
        @click="editor.chain().focus().toggleHeading({level: 3}).run()"
      >
        <VIcon
          icon="tabler-h-3"
          class="font-weight-medium"
        />
      </VBtn>
      
      <VBtn
        size="small"
        icon
        rounded
        :variant="editor.isActive('bold') ? 'tonal' : 'text'"
        :color="editor.isActive('bold') ? 'primary' : 'default'"
        @click="editor.chain().focus().toggleBold().run()"
      >
        <VIcon
          icon="tabler-bold"
          class="font-weight-medium"
        />
      </VBtn>

      <VBtn
        size="small"
        icon
        rounded
        :variant="editor.isActive('underline') ? 'tonal' : 'text'"
        :color="editor.isActive('underline') ? 'primary' : 'default'"
        @click="editor.commands.toggleUnderline()"
      >
        <VIcon icon="tabler-underline" />
      </VBtn>

      <VBtn
        size="small"
        icon
        rounded
        :variant="editor.isActive('italic') ? 'tonal' : 'text'"
        :color="editor.isActive('italic') ? 'primary' : 'default'"
        @click="editor.chain().focus().toggleItalic().run()"
      >
        <VIcon
          icon="tabler-italic"
          class="font-weight-medium"
        />
      </VBtn>

      <VBtn
        size="small"
        icon
        rounded
        :variant="editor.isActive('strike') ? 'tonal' : 'text'"
        :color="editor.isActive('strike') ? 'primary' : 'default'"
        @click="editor.chain().focus().toggleStrike().run()"
      >
        <VIcon
          icon="tabler-strikethrough"
          size="20"
        />
      </VBtn>

      <VBtn
        size="small"
        icon
        rounded
        :variant="editor.isActive('bulletList') ? 'tonal' : 'text'"
        :color="editor.isActive('bulletList') ? 'primary' : 'default'"
        @click="editor.chain().focus().toggleBulletList().run()"
      >
        <VIcon
          icon="tabler-list"
          class="font-weight-medium"
        />
      </VBtn>

      <VBtn
        size="small"
        icon
        rounded
        :variant="editor.isActive({ textAlign: 'left' }) ? 'tonal' : 'text'"
        :color="editor.isActive({ textAlign: 'left' }) ? 'primary' : 'default'"
        @click="editor.chain().focus().setTextAlign('left').run()"
      >
        <VIcon
          icon="tabler-align-left"
          size="20"
        />
      </VBtn>

      <VBtn
        size="small"
        icon
        rounded
        :color="editor.isActive({ textAlign: 'center' }) ? 'primary' : 'default'"
        :variant="editor.isActive({ textAlign: 'center' }) ? 'tonal' : 'text'"
        @click="editor.chain().focus().setTextAlign('center').run()"
      >
        <VIcon
          icon="tabler-align-center"
          size="20"
        />
      </VBtn>

      <VBtn
        size="small"
        icon
        rounded
        :variant="editor.isActive({ textAlign: 'right' }) ? 'tonal' : 'text'"
        :color="editor.isActive({ textAlign: 'right' }) ? 'primary' : 'default'"
        @click="editor.chain().focus().setTextAlign('right').run()"
      >
        <VIcon
          icon="tabler-align-right"
          size="20"
        />
      </VBtn>

      <VBtn
        size="small"
        icon
        rounded
        :variant="editor.isActive({ textAlign: 'justify' }) ? 'tonal' : 'text'"
        :color="editor.isActive({ textAlign: 'justify' }) ? 'primary' : 'default'"
        @click="editor.chain().focus().setTextAlign('justify').run()"
      >
        <VIcon
          icon="tabler-align-justified"
          size="20"
        />
      </VBtn>
      
      <VBtn
        size="small"
        icon
        rounded
        @click="open"
      >
        <VIcon
          icon="tabler-photo-scan"
          size="20"
        />
      </VBtn>

      <VBtn
        size="small"
        icon
        rounded
        @click="editor.commands.insertTable({rows: 3, cols: 3, withHeaderRow: true})"
      >
        <VIcon
          icon="tabler-table"
          size="20"
        />
      </VBtn>
    </div>
    <div
      v-if="editor?.isActive('table') || editor?.isActive('tableCell')"
      class="d-flex gap-1 flex-wrap align-center"
    >
      <VBtn
        size="small"
        icon
        rounded
        title="Toggle Header Row"
        @click="editor.commands.toggleHeaderRow()"
      >
        <VIcon
          icon="tabler-layout-navbar"
          size="20"
        />
      </VBtn>
      
      <VBtn
        size="small"
        icon
        rounded
        title="Insert Row Before"
        @click="editor.commands.addRowBefore()"
      >
        <VIcon
          icon="tabler-row-insert-top"
          size="20"
        />
      </VBtn>
      
      <VBtn
        size="small"
        icon
        rounded
        title="Insert Row After"
        @click="editor.commands.addRowAfter()"
      >
        <VIcon
          icon="tabler-row-insert-bottom"
          size="20"
        />
      </VBtn>
      
      <VBtn
        size="small"
        icon
        rounded
        title="Delete Row"
        @click="editor.commands.deleteRow()"
      >
        <VIcon
          icon="tabler-row-remove"
          size="20"
        />
      </VBtn>
      
      <VBtn
        size="small"
        icon
        rounded
        title="Insert Column Before"
        @click="editor.commands.addColumnBefore()"
      >
        <VIcon
          icon="tabler-column-insert-left"
          size="20"
        />
      </VBtn>
      
      <VBtn
        size="small"
        icon
        rounded
        title="Insert Column After"
        @click="editor.commands.addColumnAfter()"
      >
        <VIcon
          icon="tabler-column-insert-right"
          size="20"
        />
      </VBtn>
      
      <VBtn
        size="small"
        icon
        rounded
        title="Delete Column"
        @click="editor.commands.deleteColumn()"
      >
        <VIcon
          icon="tabler-column-remove"
          size="20"
        />
      </VBtn>
    </div>

    <VDivider class="my-4" />

    <EditorContent
      ref="editorRef"
      :editor="editor"
    />
  </div>
</template>
<style lang="scss">
@import "@styles/tiptap/table";

.productDescriptionEditor {
  .ProseMirror {
    &-focused {
      outline: none;
    }
  }

  .tiptap {
    padding: 0 !important;
    min-block-size: 12vh;

    p {
      margin-block-end: 0;
    }

    p.is-editor-empty:first-child::before {
      block-size: 0;
      color: #adb5bd;
      content: attr(data-placeholder);
      float: inline-start;
      pointer-events: none;
    }

    img {
      display: block;
      inline-size: auto;
      max-block-size: 500px;

      &.ProseMirror-selectednode {
        outline: 3px solid var(--purple);
      }
    }
  }
}
</style>
