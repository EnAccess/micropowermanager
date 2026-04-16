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
      :md-active.sync="editDialogActive"
      :md-close-on-esc="true"
      :md-click-outside-to-close="true"
    >
      <md-dialog-title>{{ $tc("phrases.editVillage") }}</md-dialog-title>
      <md-dialog-content>
        <md-field>
          <label>{{ $tc("words.name") }}</label>
          <md-input v-model="editName" />
        </md-field>
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
import { notify } from "@/mixins/notify.js"
import { CityService } from "@/services/CityService.js"
import Widget from "@/shared/Widget.vue"

export default {
  name: "VillageList",
  mixins: [notify],
  components: {
    Widget,
  },
  data() {
    return {
      cityService: new CityService(),
      cities: [],
      loading: false,
      editDialogActive: false,
      editingCity: null,
      editName: "",
    }
  },
  mounted() {
    this.loadCities()
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
    goToAddVillage() {
      this.$router.push("/locations/add-village")
    },
    openEditDialog(city) {
      this.editingCity = city
      this.editName = city.name || ""
      this.editDialogActive = true
    },
    async saveEdit() {
      if (!this.editName || !this.editName.trim()) {
        this.alertNotify("error", this.$tc("phrases.nameRequired"))
        return
      }
      try {
        await this.cityService.updateCity(this.editingCity.id, {
          name: this.editName.trim(),
          miniGridId: this.editingCity.mini_grid_id,
          countryId: this.editingCity.country_id,
        })
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
</style>
