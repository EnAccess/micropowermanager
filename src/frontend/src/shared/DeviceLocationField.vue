<template>
  <div class="coordinate-section">
    <div class="md-layout md-gutter">
      <div class="md-layout-item md-size-50 md-small-size-100">
        <md-field :class="{ 'md-invalid': !latitudeValid }">
          <label for="device_latitude">{{ $tc("words.latitude") }}</label>
          <md-input
            id="device_latitude"
            name="device_latitude"
            v-model="latitude"
          />
          <span class="md-error">Enter a latitude between -90 and 90</span>
        </md-field>
      </div>
      <div class="md-layout-item md-size-50 md-small-size-100">
        <md-field :class="{ 'md-invalid': !longitudeValid }">
          <label for="device_longitude">{{ $tc("words.longitude") }}</label>
          <md-input
            id="device_longitude"
            name="device_longitude"
            v-model="longitude"
          />
          <span class="md-error">Enter a longitude between -180 and 180</span>
        </md-field>
      </div>
    </div>
    <md-button
      class="md-primary md-raised coordinate-button"
      type="button"
      @click="openLocationPicker"
    >
      Set Device Location
    </md-button>
    <p class="coordinate-hint">
      Device location defaults to the customer's primary address. Use the map to
      adjust these coordinates if needed.
    </p>

    <md-dialog
      :md-active.sync="showLocationPicker"
      style="max-width: 70rem; margin: auto"
    >
      <md-dialog-title>Select Device Location</md-dialog-title>
      <md-dialog-content style="overflow-y: visible">
        <p class="coordinate-dialog-hint">
          Click on the map to place the device marker. Only one marker is
          allowed.
        </p>
        <DeviceLocationPickerMap
          v-if="showLocationPicker"
          :key="locationPickerKey"
          :mapping-service="mappingService"
          :map-container-id="locationPickerMapId"
          :initial-location="initialLocationArray"
          :marker-icon="markerIcon"
          @location-selected="pendingLocation = $event"
          @location-cleared="pendingLocation = null"
        />
      </md-dialog-content>
      <md-dialog-actions>
        <md-button
          class="md-primary md-raised"
          type="button"
          :disabled="!pendingLocation"
          @click="confirmLocation"
        >
          Use Location
        </md-button>
        <md-button type="button" @click="closeLocationPicker">
          {{ $tc("words.cancel") }}
        </md-button>
      </md-dialog-actions>
    </md-dialog>
  </div>
</template>

<script>
import defaultMarker from "leaflet/dist/images/marker-icon.png"

import { hasCoordinates, isValidCoordinate } from "@/Helpers/Utils.js"
import DeviceLocationPickerMap from "@/modules/Client/Appliances/DeviceLocationPickerMap.vue"
import { MappingService } from "@/services/MappingService.js"

export default {
  name: "DeviceLocationField",
  components: { DeviceLocationPickerMap },
  props: {
    value: {
      type: Object,
      default: null,
    },
    fallbackLocation: {
      type: Array,
      default: null,
    },
    markerIcon: {
      type: String,
      default: defaultMarker,
    },
  },
  data() {
    return {
      mappingService: new MappingService(),
      pendingLocation: null,
      showLocationPicker: false,
      locationPickerKey: 0,
      locationPickerMapId: "",
    }
  },
  created() {
    this.locationPickerMapId = `device-location-map-${this._uid}`
    if (!this.value && this.fallbackLocation) this.emitFallback()
  },
  computed: {
    latitude: {
      get() {
        return this.value ? this.value.lat : ""
      },
      set(latitude) {
        this.$emit("input", { lat: latitude, lon: this.value?.lon ?? null })
      },
    },
    longitude: {
      get() {
        return this.value ? this.value.lon : ""
      },
      set(longitude) {
        this.$emit("input", { lat: this.value?.lat ?? null, lon: longitude })
      },
    },
    latitudeValid() {
      return !this.value || isValidCoordinate(this.value.lat, "lat")
    },
    longitudeValid() {
      return !this.value || isValidCoordinate(this.value.lon, "lon")
    },
    initialLocationArray() {
      return hasCoordinates(this.value)
        ? [this.value.lat, this.value.lon]
        : this.fallbackLocation
    },
  },
  watch: {
    markerIcon(icon) {
      this.mappingService.setMarkerUrl(icon)
      this.locationPickerKey += 1
    },
    fallbackLocation() {
      if (!hasCoordinates(this.value)) this.emitFallback()
    },
  },
  methods: {
    emitFallback() {
      this.$emit(
        "input",
        this.fallbackLocation
          ? { lat: this.fallbackLocation[0], lon: this.fallbackLocation[1] }
          : null,
      )
    },
    openLocationPicker() {
      this.locationPickerKey += 1
      const center = this.initialLocationArray
      if (center) this.mappingService.setCenter(center)
      this.pendingLocation = center ? { lat: center[0], lon: center[1] } : null
      this.showLocationPicker = true
    },
    closeLocationPicker() {
      this.showLocationPicker = false
      this.pendingLocation = null
      this.locationPickerKey += 1
    },
    confirmLocation() {
      if (this.pendingLocation) {
        this.$emit("input", {
          lat: this.pendingLocation.lat,
          lon: this.pendingLocation.lon,
        })
      } else {
        this.emitFallback()
      }
      this.closeLocationPicker()
    },
  },
}
</script>

<style scoped lang="scss">
.coordinate-section {
  margin-top: 1rem;
}

.coordinate-button {
  margin-left: 0;
}

.coordinate-hint,
.coordinate-dialog-hint {
  font-size: 0.875rem;
  color: #555;
}
</style>
