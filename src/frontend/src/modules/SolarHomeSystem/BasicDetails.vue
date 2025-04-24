<template>
  <widget
    :title="$tc('SolarHomeSystem')"
    color="green"
    :subscriber="'shs-details'"
  >
    <md-list class="md-double-line">
      <md-list-item>
        <div class="md-list-item-text">
          <span>{{ $tc("phrases.serialNumber") }}</span>
          <span>{{ shs.serialNumber }}</span>
        </div>
      </md-list-item>
      <md-divider></md-divider>
      <md-list-item>
        <div class="md-list-item-text">
          <span>{{ $tc("words.manufacturer") }}</span>
          <span>{{ shs.manufacturer?.name || "-" }}</span>
        </div>
      </md-list-item>
      <md-divider></md-divider>
      <md-list-item>
        <div class="md-list-item-text">
          <span>{{ $tc("words.appliance") }}</span>
          <span>{{ shs.appliance?.name || "-" }}</span>
        </div>
      </md-list-item>
      <md-divider></md-divider>
      <md-list-item>
        <div class="md-list-item-text">
          <span>{{ $tc("phrases.lastUpdate") }}</span>
          <span>{{ timeForTimeZone(shs.updatedAt) }}</span>
        </div>
      </md-list-item>
    </md-list>
  </widget>
</template>

<script>
import Widget from "@/shared/Widget.vue"
import { timing } from "@/mixins"

export default {
  name: "BasicDetails",
  mixins: [timing],
  components: {
    Widget,
  },
  props: {
    shs: {
      type: Object,
      required: true,
    },
  },
  mounted() {
    this.$emit("widget-loaded", "details")
  },
}
</script>
