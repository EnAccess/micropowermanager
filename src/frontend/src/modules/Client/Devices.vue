<template>
  <div>
    <widget
      :title="$tc('words.devices')"
      color="green"
      :subscriber="subscriber"
    >
      <div class="md-layout md-gutter">
        <div
          class="md-layout-item md-medium-size-100 md-large-size-100 md-small-size-100"
        >
          <md-table
            style="width: 100%"
            v-model="this.devices"
            md-card
            md-fixed-header
          >
            <md-table-row
              slot="md-table-row"
              slot-scope="{ item }"
              :style="{ cursor: 'pointer' }"
            >
              <md-table-cell md-label="#">
                <md-icon @click="setMapCenter(item.id)" style="cursor: pointer">
                  place
                </md-icon>
              </md-table-cell>
              <md-table-cell
                :md-label="$tc('phrases.serialNumber')"
                md-sort-by="device_serial"
                @click.native="navigateToDeviceDetail(item)"
              >
                {{ item.device_serial }}
              </md-table-cell>
              <md-table-cell
                :md-label="$tc('words.deviceType')"
                md-sort-by="device_type"
                @click.native="navigateToDeviceDetail(item)"
              >
                {{ $tc(`words.${item.device_type}`) }}
              </md-table-cell>
            </md-table-row>
          </md-table>
        </div>
      </div>
    </widget>
  </div>
</template>

<script>
import Widget from "@/shared/widget.vue"
import { EventBus } from "@/shared/eventbus"
import { getSolarHomeSystemDetailRoute } from "@/repositories/SolarHomeSystemRepository"
import { getMeterDetailRoute } from "@/repositories/MeterRepository"
import { getEBikeDetailRoute } from "@/repositories/EBikeRepository"
import { getDeviceDetailRoute } from "@/repositories/DeviceRepository"
export default {
  name: "Devices",
  props: {
    devices: {
      required: true,
      type: Array,
    },
  },
  components: {
    Widget,
  },
  data() {
    return {
      subscriber: "client-device-list",
    }
  },
  mounted: function () {
    EventBus.$emit("widgetContentLoaded", this.subscriber, this.devices.length)
  },
  /*************  ✨ Windsurf Command ⭐  *************/
  /**
   * Emit event to set map center for the given device
   *
   * @param {Object} device - device object
   */
  /*******  b22be44d-29b2-44df-8ac2-f22996c0ee68  *******/ methods: {
    setMapCenter(device) {
      EventBus.$emit("setMapCenterForDevice", device)
    },
    navigateToDeviceDetail(device) {
      let route = ""

      switch (device.device_type) {
        case "meter":
          route = getMeterDetailRoute(device.device_serial)
          break
        case "solar_home_system":
          route = getSolarHomeSystemDetailRoute(device.id)
          break
        case "e_bike":
          route = getEBikeDetailRoute(device.id)
          break
        default:
          route = getDeviceDetailRoute(device.id)
      }
      this.$router.push(route)
    },
  },
}
</script>
