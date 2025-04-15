<template>
  <div class="page-container">
    <widget
      :id="'shs-list'"
      :title="$tc('words.meter', 2)"
      :paginator="meters.paginator"
      :search="true"
      :subscriber="subscriber"
      :route_name="'/meters'"
      color="green"
    >
      <md-table md-card style="margin-left: 0">
        <md-table-row>
          <md-table-head>
            <md-icon>add</md-icon>
            {{ $tc("phrases.serialNumber") }}
          </md-table-head>
          <md-table-head>
            {{ $tc("words.tariff") }}
          </md-table-head>
          <md-table-head>
            {{ $tc("words.manufacturer") }}
          </md-table-head>
          <md-table-head>{{ $tc("words.type") }}</md-table-head>
          <md-table-head>
            {{ $tc("phrases.lastUpdate") }}
          </md-table-head>
        </md-table-row>
        <md-table-row
          v-for="meter in meters.list"
          :key="meter.id"
          style="cursor: pointer"
          @click="meterDetail(meter.serialNumber)"
        >
          <md-table-cell>{{ meter.serialNumber }}</md-table-cell>
          <md-table-cell>{{ meter.tariff }}</md-table-cell>
          <md-table-cell>
            {{ meter.manufacturer.manufacturerName }}
          </md-table-cell>
          <md-table-cell>
            {{ meter.type }}
            <md-icon v-if="meter.online">wifi</md-icon>
          </md-table-cell>
          <md-table-cell>
            {{ timeForTimeZone(meter.lastUpdate) }}
          </md-table-cell>
        </md-table-row>
      </md-table>
    </widget>
  </div>
</template>

<script>
import Widget from "@/shared/Widget.vue"
import { Meters } from "@/services/MeterService"
import { Manufacturers } from "@/services/ManufacturerService"
import { EventBus } from "@/shared/eventbus"
import { timing } from "@/mixins/timing"

export default {
  name: "Meters",
  mixins: [timing],
  components: { Widget },
  data() {
    return {
      meters: new Meters(),
      manufacturers: new Manufacturers(),
      subscriber: "meterList",
    }
  },

  mounted() {
    EventBus.$on("pageLoaded", this.reloadList)
    EventBus.$on("searching", this.searching)
    EventBus.$on("end_searching", this.endSearching)
  },
  beforeDestroy() {
    EventBus.$off("pageLoaded", this.reloadList)
    EventBus.$off("searching", this.searching)
    EventBus.$off("end_searching", this.endSearching)
  },
  methods: {
    async reloadList(subscriber, data) {
      if (subscriber !== this.subscriber) {
        return
      }
      await this.meters.updateList(data)
      EventBus.$emit(
        "widgetContentLoaded",
        this.subscriber,
        this.meters.list.length,
      )
    },
    confirmDelete(meter) {
      this.$swal({
        type: "question",
        title: this.$tc("phrases.deleteMeter"),
        width: "25%",
        confirmButtonText: this.$tc("words.confirm"),
        showCancelButton: true,
        cancelButtonText: this.$tc("words.cancel"),
        focusCancel: true,
        html:
          '<div style="text-align: left; padding-left: 5rem" class="checkbox">' +
          "  <label>" +
          '    <input type="checkbox" name="confirmation" id="confirmation" >' +
          this.$tc("phrases.deleteMeter", 2, {
            serialNumber: meter.serialNumber,
          }) +
          "  </label>" +
          "</div>",
      }).then((result) => {
        let answer = document.getElementById("confirmation").checked
        if ("value" in result) {
          //delete customer
          if (answer) {
            this.deleteMeter(meter.id)
          } else {
            const Toast = this.$swal.mixin({
              toast: true,
              position: "top-end",
              showConfirmButton: false,
              timer: 5000,
              timerProgressBar: true,
              onOpen: (toast) => {
                toast.addEventListener("mouseenter", this.$swal.stopTimer)
                toast.addEventListener("mouseleave", this.$swal.resumeTimer)
              },
            })

            Toast.fire({
              type: "warning",
              title: this.$tc("phrases.deleteMeterNotify", 1),
            })
          }
        }
      })
    },
    deleteMeter(meterId) {
      axios.delete(resources.meters.delete + meterId).then(() => {
        const Toast = this.$swal.mixin({
          toast: true,
          //position: 'center',
          showConfirmButton: false,
          timer: 2500,
          timerProgressBar: true,
        })

        Toast.fire({
          type: "success",
          title: this.$tc("phrases.deleteMeterNotify", 2),
        }).then(() => {
          location.reload()
        })
      })
    },
    searching(searchTerm) {
      this.meters.search(searchTerm)
    },
    endSearching() {
      this.meters.showAll()
    },
    meterDetail(serialNumber) {
      this.$router.push({ path: "/meters/" + serialNumber })
    },
  },
}
</script>

<style scoped></style>
