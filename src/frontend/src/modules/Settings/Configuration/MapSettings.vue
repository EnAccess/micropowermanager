<template>
  <div class="md-layout md-gutter">
    <div
      class="md-layout md-gutter md-size-50 md-small-size-100"
      style="padding: 1rem"
    >
      <div class="md-layout-item md-size-33 md-small-size-50">
        <md-field :class="{ 'md-invalid': errors.has('Zoom') }">
          <label for="Zoom">Default Zoom</label>
          <md-input
            type="number"
            id="Zoom"
            name="Zoom"
            maxLength="1"
            v-model="mapSettingsService.mapSettings.zoom"
            v-validate="'integer|between:0,9'"
          ></md-input>
          <span class="md-error">{{ errors.first("Zoom") }}</span>
        </md-field>
      </div>
      <div class="md-layout-item md-size-33 md-small-size-50">
        <md-field>
          <label for="provider">Default Provider</label>
          <md-select
            v-model="mapSettingsService.mapSettings.provider"
            name="provider"
            id="provider"
          >
            <md-option
              v-for="provider in mapProvider"
              :key="provider"
              :value="provider"
            >
              {{ provider }}
            </md-option>
          </md-select>
        </md-field>
      </div>
      <div class="md-layout-item md-size-100 md-small-size-100">
        <md-subheader>Set Map Starting Points</md-subheader>
      </div>
      <div class="md-layout-item md-size-33 md-small-size-50">
        <md-field :class="{ 'md-invalid': errors.has($tc('words.latitude')) }">
          <label for="latitude">{{ $tc("words.latitude") }}</label>
          <md-input
            type="number"
            id="latitude"
            :name="$tc('words.latitude')"
            v-model="mapSettingsService.mapSettings.latitude"
            step="any"
            @change="setCenterPoints"
            maxlength="9"
            v-validate="'required|decimal:5|max:8'"
          />
          <span class="md-error">
            {{ errors.first($tc("words.latitude")) }}
          </span>
        </md-field>
      </div>
      <div class="md-layout-item md-size-33 md-small-size-50">
        <md-field
          :class="{
            'md-invalid': errors.has($tc('words.longitude')),
          }"
        >
          <label for="longitude">{{ $tc("words.longitude") }}</label>
          <md-input
            type="number"
            id="longitude"
            :name="$tc('words.longitude')"
            v-model="mapSettingsService.mapSettings.longitude"
            step="any"
            @change="setCenterPoints"
            maxlength="9"
            v-validate="'required|decimal:5|max:8'"
          />
          <span class="md-error">
            {{ errors.first($tc("words.longitude")) }}
          </span>
        </md-field>
      </div>
      <div class="md-layout-item md-size-34 md-small-size-100">
        <md-button
          class="md-primary md-dense md-raised"
          @click="updateMapSettings"
        >
          Save
        </md-button>
      </div>
    </div>
    <div class="map-area md-layout md-size-50" @click="getLatLon">
      <settings-map
        :mapping-service="mappingService"
        ref="settingsMap"
        :mutating-center="mutatingCenter"
        :key="mapKey"
      />
    </div>
    <md-progress-bar v-if="progress" md-mode="indeterminate"></md-progress-bar>
  </div>
</template>

<script>
import { MapSettingsService } from "@/services/MapSettingsService"
import { EventBus } from "@/shared/eventbus"
import SettingsMap from "@/modules/Map/SettingsMap.vue"
import { MappingService } from "@/services/MappingService"

export default {
  name: "MapSettings",
  components: { SettingsMap },
  props: {
    mapSettings: {
      type: Object,
    },
  },
  data() {
    return {
      mapSettingsService: new MapSettingsService(),
      mappingService: new MappingService(),
      mutatingCenter: [],
      progress: false,
      mapKey: 1,
      mapProvider: ["Bing Maps", "Open Street Map"],
    }
  },
  mounted() {
    this.$refs.settingsMap.map._onResize()
  },
  created() {
    this.mapSettingsService.mapSettings = this.mapSettings
  },
  methods: {
    async updateMapSettings() {
      this.showLoadingIndicator()
      const validator = await this.$validator.validateAll()
      if (!validator) {
        this.hideLoadingIndicator()
        return
      }

      try {
        await this.mapSettingsService.update()
        this.updateMapSettingsStore()
        EventBus.$emit("Settings")
      } catch (e) {
        this.alertNotify("error", "Map settings update failed")
      }
      this.hideLoadingIndicator()
    },
    async setCenterPoints() {
      const validator = await this.$validator.validateAll()
      if (!validator) {
        return
      }
      this.mutatingCenter = [
        this.mapSettingsService.mapSettings.latitude,
        this.mapSettingsService.mapSettings.longitude,
      ]
    },
    getLatLon() {
      const { lat, lng, zoom } = this.$refs.settingsMap.getLatLng()
      this.mapSettingsService.mapSettings.latitude = lat
      this.mapSettingsService.mapSettings.longitude = lng
      this.mapSettingsService.mapSettings.zoom = zoom
      this.setCenterPoints()
    },
    updateMapSettingsStore() {
      this.$store
        .dispatch(
          "settings/setMapSettings",
          this.mapSettingsService.mapSettings,
        )
        .then(() => {
          this.alertNotify("success", "Updated Successfully")
          this.reRenderMap()
        })
        .catch(() => {
          this.alertNotify("error", "Map settings update failed")
        })
    },
    showLoadingIndicator() {
      this.progress = true
    },
    hideLoadingIndicator() {
      this.progress = false
    },
    reRenderMap() {
      this.mapKey++
    },
  },
}
</script>

<style lang="css">
.map-area {
  display: block; /* or any other display property you want initially */
}

@media only screen and (max-width: 767px) {
  .map-area {
    display: none !important;
  }
}
</style>
