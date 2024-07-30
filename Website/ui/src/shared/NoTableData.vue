<template>
  <div>
    <md-table md-sort="id" md-sort-order="asc">
      <md-table-row>
        <md-table-head v-for="(item, index) in headers" :key="index">
          {{ item }}
        </md-table-head>
      </md-table-row>
      <md-table-row>
        <md-table-cell :colspan="headers.length">
          <span>There is no available {{ tableName }} data</span>
        </md-table-cell>
      </md-table-row>
    </md-table>
  </div>
</template>
<script>
export default {
  name: "NoTableData",
  data() {
    return {}
  },
  props: {
    headers: {
      type: Array,
    },
    tableName: {
      type: String,
    },
  },
}
</script>
<style scoped></style>
