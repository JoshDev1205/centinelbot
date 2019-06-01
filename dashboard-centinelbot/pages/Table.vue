<template>
  <div>
    <p class="description-text">
      Estas son las emergencias registrados:
    </p>
    <vue-good-table
      class="pt-4 shadow-lg table-style"
      :columns="columns"
      :rows="data"
      :pagination-options="{
        enabled: true,
        mode: 'records',
        perPage: 5
      }"
    />
  </div>
</template>

<script>
export default {
  name: 'MyTable',
  data() {
    return {
      columns: [
        {
          label: 'UBICACIÓN',
          field: 'ubicacion'
        },
        {
          label: 'TELÉFONO',
          field: 'telefono'
        },
        {
          label: 'DETALLE',
          field: 'detalle'
        },
        {
          label: 'ESTADO',
          field: 'estado'
        }
      ]
    }
  },
  async asyncData({ $axios }) {
    const data = await $axios.$get('http://localhost:3333/emergencias')
    return { data }
  }
}
</script>

<style>
.table-style{
  margin: 20px auto;
}
.description-text{
  margin-top: 2rem;
  padding: 0 18px;
}
</style>
