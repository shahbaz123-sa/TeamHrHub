<script setup>
defineOptions({
  name: 'AppAutocomplete',
  inheritAttrs: false,
})

const attrs = useAttrs()

const elementId = computed(() => {
  const _elementIdToken = attrs.id
  const _id = useId()
  return _elementIdToken ? `app-autocomplete-${_elementIdToken}` : _id
})

const label = computed(() => attrs.label)

const isReadonly = computed(() => attrs.readonly === '' || attrs.readonly === true)
const isDisabled = computed(() => attrs.disabled === '' || attrs.disabled === true)
</script>

<template>
  <div
    class="app-autocomplete flex-grow-1"
    :class="attrs.class"
  >
    <VLabel
      v-if="label"
      :for="elementId"
      class="mb-1 text-body-2"
      style="line-height: 15px;"
      :text="label"
    />

    <VAutocomplete
      v-bind="{
        ...attrs,
        class: null,
        label: undefined,
        variant: 'outlined',
        id: elementId,
        clearable: !isReadonly,
        hideDetails: 'auto',
        density: 'comfortable',
        itemTitle: attrs['item-title'] ?? 'name',
        itemValue: attrs['item-value'] ?? 'id',
        readonly: isReadonly,
        disabled: isDisabled,
      }"
    >
      <!-- pass through slots -->
      <template
        v-for="(_, name) in $slots"
        #[name]="slotProps"
      >
        <slot :name="name" v-bind="slotProps || {}" />
      </template>
    </VAutocomplete>
  </div>
</template>
