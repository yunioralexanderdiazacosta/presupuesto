<template>
  <div class="container py-4">
    <h2 class="mb-4"><i class="bi bi-diagram-3 me-2"></i>Resumen de Niveles</h2>
    <div class="flex flex-wrap gap-2 mb-4">
      <Button type="button" icon="pi pi-plus" label="Expandir Todo" @click="expandAll" />
      <Button type="button" icon="pi pi-minus" label="Colapsar Todo" @click="collapseAll" />
    </div>
    <Tree v-model:expandedKeys="expandedKeys" :value="nodes" class="w-full md:w-[30rem]" />
  </div>
</template>

<script setup>
import { ref } from 'vue';
import Tree from 'primevue/tree';
import Button from 'primevue/button';

defineProps({ levels1: Array });

function toTreeNodes(levels1) {
  return levels1.map(l1 => ({
    key: 'l1-' + l1.id,
    label: l1.name,
    children: (l1.levels2 || []).map(l2 => ({
      key: 'l2-' + l2.id,
      label: l2.name,
      children: (l2.level3s || []).map(l3 => ({
        key: 'l3-' + l3.id,
        label: l3.name,
        children: (l3.level4s || []).map(l4 => ({
          key: 'l4-' + l4.id,
          label: l4.name
        }))
      }))
    }))
  }));
}

const nodes = ref(toTreeNodes(levels1));
const expandedKeys = ref({});

function expandAll() {
  const _expandedKeys = {};
  const expand = nodes => {
    nodes.forEach(node => {
      _expandedKeys[node.key] = true;
      if (node.children) expand(node.children);
    });
  };
  expand(nodes.value);
  expandedKeys.value = _expandedKeys;
}

function collapseAll() {
  expandedKeys.value = {};
}
</script>

<style scoped>
.card {
  border-radius: 1rem;
}
.list-group-item {
  border: none;
  padding-left: 0.5rem;
  padding-right: 0.5rem;
  background: transparent;
}
.list-group .list-group {
  margin-top: 0.5rem;
}
</style>
