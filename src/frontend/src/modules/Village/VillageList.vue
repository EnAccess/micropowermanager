<template>
  <div>
    <widget
      :id="'village-list'"
      :title="$tc('words.village', 2)"
      :button="true"
      :button-text="$tc('phrases.addVillage')"
      color="primary"
      @widgetAction="goToAddVillage"
    >
      <md-table
        style="width: 100%"
        v-model="cities"
        md-card
        md-fixed-header
        v-if="cities.length"
      >
        <md-table-row slot="md-table-row" slot-scope="{ item }">
          <md-table-cell :md-label="$tc('words.name')">
            {{ item.name }}
          </md-table-cell>
          <md-table-cell :md-label="$tc('words.miniGrid')">
            {{ item.mini_grid ? item.mini_grid.name : "-" }}
          </md-table-cell>
          <md-table-cell :md-label="$tc('words.country')">
            {{ item.country ? item.country.country_name : "-" }}
          </md-table-cell>
          <md-table-cell md-label="">
            <md-button
              class="md-icon-button md-dense"
              @click="openEditDialog(item)"
            >
              <md-icon>edit</md-icon>
            </md-button>
            <md-button
              class="md-icon-button md-dense md-accent"
              @click="confirmDelete(item)"
            >
              <md-icon>delete</md-icon>
            </md-button>
          </md-table-cell>
        </md-table-row>
      </md-table>
      <div v-else class="empty-state">
        {{ $tc("phrases.noRecords") }}
      </div>
      <md-progress-bar md-mode="indeterminate" v-if="loading" />
    </widget>

    <md-dialog
      class="village-edit-dialog"
      :md-active.sync="editDialogActive"
      :md-close-on-esc="true"
      :md-click-outside-to-close="true"
    >
      <md-dialog-title>{{ $tc("phrases.editVillage") }}</md-dialog-title>
      <md-dialog-content>
        <div class="md-layout md-gutter">
          <div class="md-layout-item md-size-50 md-small-size-100">
            <md-field>
              <label>{{ $tc("words.name") }}</label>
              <md-input v-model="editName" />
            </md-field>
          </div>
          <div class="md-layout-item md-size-50 md-small-size-100">
            <md-field>
              <label for="editCountry">{{ $tc("words.country") }}</label>
              <md-select
                v-model="editCountryId"
                name="editCountry"
                id="editCountry"
              >
                <md-option
                  v-for="country in countries"
                  :value="country.id"
                  :key="country.id"
                >
                  {{ country.country_name }}
                </md-option>
              </md-select>
            </md-field>
          </div>
          <div class="md-layout-item md-size-35 md-small-size-100">
            <md-field>
              <label>{{ $tc("words.latitude") }}</label>
              <md-input v-model="cityLatLng.lat" step="any" maxlength="8" />
            </md-field>
          </div>
          <div class="md-layout-item md-size-35 md-small-size-100">
            <md-field>
              <label>{{ $tc("words.longitude") }}</label>
              <md-input v-model="cityLatLng.lon" step="any" maxlength="8" />
            </md-field>
          </div>
          <div class="md-layout-item md-size-30 md-small-size-100">
            <md-button class="md-primary" @click="setPoints">
              {{ $tc("phrases.setPoints") }}
            </md-button>
          </div>
        </div>
        <div class="map-area">
          <village-map
            v-if="editDialogActive"
            ref="villageMapRef"
            :mapping-service="mappingService"
            :marker="true"
            @locationSet="villageLocationSet"
          />
        </div>
      </md-dialog-content>
      <md-dialog-actions>
        <md-button @click="editDialogActive = false">
          {{ $tc("words.cancel") }}
        </md-button>
        <md-button class="md-primary" @click="saveEdit">
          {{ $tc("words.save") }}
        </md-button>
      </md-dialog-actions>
    </md-dialog>
  </div>
</template>

<script>
import { geoJsonToLatLon, latLonToGeoJsonPoint } from "@/Helpers/Utils.js"
import { notify } from "@/mixins/notify.js"
import { villageMapContext } from "@/mixins/villageMapContext.js"
import VillageMap from "@/modules/Map/VillageMap.vue"
import { CityService } from "@/services/CityService.js"
import { ClusterService } from "@/services/ClusterService.js"
import CountryService from "@/services/CountryService.js"
import { ICONS, MappingService } from "@/services/MappingService.js"
import { MiniGridService } from "@/services/MiniGridService.js"
import Widget from "@/shared/Widget.vue"

export default {
  name: "VillageList",
  mixins: [notify, villageMapContext],
  components: {
    VillageMap,
    Widget,
  },
  data() {
    return {
      cityService: new CityService(),
      clusterService: new ClusterService(),
      countryService: new CountryService(),
      miniGridService: new MiniGridService(),
      mappingService: new MappingService(),
      cities: [],
      countries: [],
      loading: false,
      editDialogActive: false,
      editingCity: null,
      editName: "",
      editCountryId: null,
    }
  },
  created() {
    this.mappingService.setConstantMarkerUrl(ICONS.MINI_GRID)
    this.mappingService.setMarkerUrl(ICONS.VILLAGE)
  },
  mounted() {
    this.loadCities()
    this.loadCountries()
  },
  methods: {
    async loadCities() {
      this.loading = true
      try {
        const cities = await this.cityService.getCities()
        this.cities = Array.isArray(cities) ? cities : []
      } catch (e) {
        this.alertNotify("error", e.message)
      }
      this.loading = false
    },
    async loadCountries() {
      try {
        await this.countryService.getCountries()
        this.countries = this.countryService.list
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
    goToAddVillage() {
      this.$router.push("/locations/add-village")
    },
    async openEditDialog(city) {
      this.editingCity = city
      this.editName = city.name || ""
      this.editCountryId = city.country_id || null
      const location = geoJsonToLatLon(city.location)
      this.cityLatLng.lat = location ? location.lat : null
      this.cityLatLng.lon = location ? location.lon : null
      this.editDialogActive = true

      try {
        const miniGridWithGeoData = await this.loadVillageMapContext(
          city.mini_grid_id,
        )
        if (!miniGridWithGeoData) return
        await this.$nextTick()
        const villageMap = this.$refs.villageMapRef
        if (!villageMap) return
        villageMap.map.invalidateSize()
        villageMap.drawCluster()
        villageMap.setMiniGridMarker()
        if (location) {
          villageMap.setVillageMarkerManually([location.lat, location.lon])
        }
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
    async saveEdit() {
      if (!this.editName || !this.editName.trim()) {
        this.alertNotify("error", this.$tc("phrases.nameRequired"))
        return
      }
      try {
        const cityData = {
          name: this.editName.trim(),
          miniGridId: this.editingCity.mini_grid_id,
          countryId: this.editCountryId,
        }
        if (this.cityLatLng.lat !== null && this.cityLatLng.lon !== null) {
          cityData.geoJson = latLonToGeoJsonPoint(
            this.cityLatLng.lat,
            this.cityLatLng.lon,
          )
        }
        await this.cityService.updateCity(this.editingCity.id, cityData)
        this.editDialogActive = false
        this.alertNotify("success", this.$tc("phrases.villageUpdated"))
        await this.loadCities()
      } catch (e) {
        this.alertNotify("error", e.message || this.$tc("phrases.updateFailed"))
      }
    },
    confirmDelete(city) {
      this.$swal({
        type: "question",
        title: this.$tc("phrases.deleteVillage"),
        text: this.$tc("phrases.deleteVillageNotify", 0, { name: city.name }),
        width: "35%",
        confirmButtonText: this.$tc("words.confirm"),
        showCancelButton: true,
        cancelButtonText: this.$tc("words.cancel"),
        focusCancel: true,
      }).then((result) => {
        if (result.value) {
          this.deleteVillage(city)
        }
      })
    },
    async deleteVillage(city) {
      try {
        await this.cityService.deleteCity(city.id)
        this.alertNotify("success", this.$tc("phrases.villageDeleted"))
        await this.loadCities()
      } catch (e) {
        this.alertNotify("error", e.message || this.$tc("phrases.deleteFailed"))
      }
    },
  },
}
</script>

<style lang="scss" scoped>
.empty-state {
  padding: 2rem;
  text-align: center;
  color: #777;
}

.village-edit-dialog {
  ::v-deep .md-dialog-container {
    width: 70%;
    max-width: 900px;
  }
}

.map-area {
  z-index: 1 !important;
}
</style>
