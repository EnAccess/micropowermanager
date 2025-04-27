<template>
  <widget :title="$tc('words.owner')" color="green" :subscriber="'shs-owner'">
    <md-list class="md-double-line">
      <md-list-item :to="`/people/${person.id}`">
        <div class="md-list-item-text">
          <span>{{ $tc("words.name") }}</span>
          <span>{{ person.name }} {{ person.surname }}</span>
        </div>
        <md-icon>arrow_forward</md-icon>
      </md-list-item>
    </md-list>
  </widget>
</template>

<script>
import Widget from "@/shared/Widget.vue"

export default {
  name: "Owner",
  components: {
    Widget,
  },
  props: {
    person: {
      type: Object,
      required: true,
    },
  },
  mounted() {
    this.$emit("widget-loaded", "owner")
  },
}
</script>
