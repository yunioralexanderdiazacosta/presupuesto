<template>
  <div class="container py-4">
    <div class="card shadow border-0 p-4">
      <h2 class="mb-4 text-primary">
        <i class="fas fa-sitemap me-2"></i>Resumen de Niveles
      </h2>
      <div class="table-responsive">
        <table class="table table-striped table-hover mb-0 table-sm small">
          <thead class="table-light">
            <tr>
              <th>Nivel 1</th>
              <th>Nivel 2</th>
              <th>Nivel 3</th>
              <th>Nivel 4</th>
            </tr>
          </thead>
          <tbody>
            <template v-for="(row, idx) in displayRows" :key="idx">
              <tr>
                <td v-html="row.n1"></td>
                <td v-html="row.n2"></td>
                <td v-html="row.n3"></td>
                <td v-html="row.n4"></td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  levels1: {
    type: Array,
    default: () => []
  }
});

function buildTableRows(levels1) {
  const rows = [];
  (levels1 || []).forEach(l1 => {
    if (!l1.levels2 || l1.levels2.length === 0) {
      rows.push({ n1: l1.name, n2: '', n3: '', n4: '' });
    } else {
      l1.levels2.forEach(l2 => {
        if (!l2.level3s || l2.level3s.length === 0) {
          rows.push({ n1: l1.name, n2: l2.name, n3: '', n4: '' });
        } else {
          l2.level3s.forEach(l3 => {
            if (!l3.level4s || l3.level4s.length === 0) {
              rows.push({ n1: l1.name, n2: l2.name, n3: l3.name, n4: '' });
            } else {
              l3.level4s.forEach(l4 => {
                rows.push({ n1: l1.name, n2: l2.name, n3: l3.name, n4: l4.name });
              });
            }
          });
        }
      });
    }
  });
  return rows;
}

function addRowspanAndIcons(rows) {
  // Agrupa y agrega rowspan e íconos, devolviendo un array de filas con celdas html
  const result = [];
  let prevN1 = '', prevN2 = '', prevN3 = '';
  let n1Count = 0, n2Count = 0, n3Count = 0;
  let n1Idx = 0, n2Idx = 0, n3Idx = 0;

  // Contar spans
  const n1Spans = {}, n2Spans = {}, n3Spans = {};
  rows.forEach((row, i) => {
    if (row.n1 !== prevN1) {
      n1Count = rows.filter(r => r.n1 === row.n1).length;
      n1Spans[i] = n1Count;
      prevN1 = row.n1;
    }
    if (row.n2 !== prevN2 || row.n1 !== prevN1) {
      n2Count = rows.filter(r => r.n1 === row.n1 && r.n2 === row.n2).length;
      n2Spans[i] = n2Count;
      prevN2 = row.n2;
    }
    if (row.n3 !== prevN3 || row.n2 !== prevN2 || row.n1 !== prevN1) {
      n3Count = rows.filter(r => r.n1 === row.n1 && r.n2 === row.n2 && r.n3 === row.n3).length;
      n3Spans[i] = n3Count;
      prevN3 = row.n3;
    }
  });

  prevN1 = prevN2 = prevN3 = '';
  rows.forEach((row, i) => {
    const n1 = (row.n1 !== prevN1)
      ? `<span><i class='fas fa-layer-group text-info me-2'></i>${row.n1}</span>`
      : '';
    const n2 = (row.n2 !== prevN2 || row.n1 !== prevN1)
      ? `<span><i class='fas fa-layer-group text-secondary me-2'></i>${row.n2 || ''}</span>`
      : '';
    const n3 = (row.n3 !== prevN3 || row.n2 !== prevN2 || row.n1 !== prevN1)
      ? `<span><i class='fas fa-layer-group text-success me-2'></i>${row.n3 || ''}</span>`
      : '';
    const n4 = row.n4 ? `<span><i class='fas fa-layer-group text-warning me-2'></i>${row.n4}</span>` : '';
    result.push({ n1, n2, n3, n4 });
    prevN1 = row.n1;
    prevN2 = row.n2;
    prevN3 = row.n3;
  });
  return result;
}

const tableRows = buildTableRows(props.levels1);
const displayRows = addRowspanAndIcons(tableRows);
</script>

<style scoped>
.card {
  border-radius: 1rem;
}
</style>
