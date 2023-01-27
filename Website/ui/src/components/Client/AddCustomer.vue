<template>
  <widget
      v-if="show"
      title="simdcik yok"
      color="green">

    <md-card>
      <md-card-content>
        <div class="md-layout md-gutter">
          <div class="md-layout-item md-size-100">
            <form class="md-layout md-gutter" data-vv-scope="customer-add-form">
              <div class="md-layout-item md-size-100">
                <h4 style="font-size: 1.2rem; margin:0; border-bottom: solid 1px #dedede">Personal</h4>
              </div>
              <!-- name -->
              <div class="md-layout-item md-size-33 md-small-size-100">
                <md-field :class="{'md-invalid': errors.has('customer-add-form.customer_name')}">
                  <label for="customer_name">{{ $tc('words.name') }}</label>
                  <md-input id="customer_name" name="customer_name" v-model="customer.name"
                            v-validate="'required|min:3'"/>
                  <span class="md-error">{{ errors.first('customer-add-form.customer_name') }}</span>
                </md-field>
              </div>

              <!-- surname -->
              <div class="md-layout-item md-size-33 md-small-size-100">
                <md-field :class="{'md-invalid': errors.has('customer-add-form.customer_surname')}">
                  <label for="customer_surname">{{ $tc('words.surname') }}</label>
                  <md-input id="customer_surname" name="customer_surname" v-model="customer.surname"
                            v-validate="'required|min:3'"/>
                  <span class="md-error">{{ errors.first('customer-add-form.customer_surname') }}</span>
                </md-field>
              </div>

              <!-- phone -->
              <div class="md-layout-item md-size-33 md-small-size-100">
                <md-field :class="{'md-invalid': errors.has('customer-add-form.customer_phone')}">
                  <label for="customer_phone">{{ $tc('words.phone') }}</label>
                  <md-input id="customer_phone" name="customer_phone" v-model="customer.phone"
                            v-validate="'required|min:3'" placeholder="Phone (+___ ____ ____)}"/>
                  <span class="md-error">{{ errors.first('customer-add-form.customer_phone') }}</span>
                </md-field>
              </div>

              <!--cluster list-->
              <div class="md-layout-item md-size-25 md-small-size-100 ">
                <md-field :class="{'md-invalid': errors.has('customer-add-form.cluster')}">
                  <label for="cluster">{{ $tc('words.cluster') }}</label>
                  <md-select
                      v-model="customer.cluster"
                      name="cluster"
                      id="meterTariff"
                      v-validate="'required'">
                    <md-option v-for="cluster in clusterService.clusters" :value="cluster.id"
                               :key="cluster.id">
                      {{cluster.name}}
                    </md-option>
                  </md-select>
                  <span class="md-error">{{ errors.first('customer-add-form.cluster') }}</span>
                </md-field>
              </div>

              <!--city list-->
              <div class="md-layout-item md-size-25 md-small-size-100 ">
                <md-field :class="{'md-invalid': errors.has('customer-add-form.city')}">
                  <label for="cluster">{{ $tc('words.city') }}</label>
                  <md-select
                      v-model="customer.city"
                      name="cluster"
                      id="meterTariff"
                      v-validate="'required'">
                    <md-option v-for="city in citiesInCluster" :value="city.id"
                               :key="city.id">
                      {{city.name}}
                    </md-option>
                  </md-select>
                  <span class="md-error">{{ errors.first('customer-add-form.city') }}</span>
                </md-field>
              </div>

              <!-- Meter Related Input -->
              <div class="md-layout-item md-size-100">
                <h4 style="font-size: 1.2rem; margin: 20px 0 0 0; border-bottom: solid 1px #dedede">Meters</h4>
              </div>

              <!--meter manufacturer-->
              <div class="md-layout-item md-size-25 md-small-size-100 ">
                <md-field :class="{'md-invalid': errors.has('customer-add-form.meterManufacturer')}">
                  <label for="meterManufacturer">{{ $tc('words.meter') }}</label>
                  <md-select
                      v-model="customer.meterManufacturer"
                      name="meterManufacturer"
                      id="meterManufacturer"
                      v-validate="'required'">
                    <md-option v-for="meterManufacturer in meterManufacturerService.list" :value="meterManufacturer.id"
                               :key="meterManufacturer.id">
                      {{meterManufacturer.manufacturerName}}
                    </md-option>
                  </md-select>
                  <span class="md-error">{{ errors.first('words.meterManufacturer') }}</span>
                </md-field>
              </div>

              <!--meter types-->
              <div v-if="meterTypeService.meterTypeListLoaded" class="md-layout-item md-size-25 md-small-size-100 ">
                <md-field :class="{'md-invalid': errors.has('customer-add-form.meterType')}">
                  <label for="meterType">{{ $tc('words.meter') }}</label>
                  <md-select
                      v-model="customer.meterType"
                      name="meterType"
                      id="meterType"
                      v-validate="'required'">
                    <md-option v-for="meterType in meterTypeService.meterTypesList" :value="meterType.id"
                               :key="meterType.id">
                      {{meterType.name}}
                    </md-option>
                  </md-select>
                  <span class="md-error">{{ errors.first('customer-add-form.meterType') }}</span>
                </md-field>
              </div>

              <!-- serial number -->
              <div class="md-layout-item md-size-25 md-small-size-100">
                <md-field :class="{'md-invalid': errors.has('customer-add-form.customer_serial_number')}">
                  <label for="customer_serial_number">Serial S/N</label>
                  <md-input id="customer_serial_number" name="customer_serial_number" v-model="customer.meterSerialNumber"
                            v-validate="'required|min:3'"/>
                  <span class="md-error">{{ errors.first('customer-add-form.customer_serial_number') }}</span>
                </md-field>
              </div>

              <!--meter tariffs-->
              <div v-if="meterTypeService.meterTypeListLoaded" class="md-layout-item md-size-25 md-small-size-100 ">
                <md-field :class="{'md-invalid': errors.has('customer-add-form.meterTariff')}">
                  <label for="meterTariff">{{ $tc('words.tariff',2) }}</label>
                  <md-select
                      v-model="customer.meterTariff"
                      name="meterTariff"
                      id="meterTariff"
                      v-validate="'required'">
                    <md-option v-for="meterTariff in meterTariffService.list" :value="meterTariff.id"
                               :key="meterTariff.id">
                      {{meterTariff.name}}
                    </md-option>
                  </md-select>
                  <span class="md-error">{{ errors.first('customer-add-form.meterTariff') }}</span>
                </md-field>
              </div>

            </form>
          </div>
        </div>
      </md-card-content>
    </md-card>

  </widget>
</template>


<script>

import Widget from '@/shared/widget'
import {EventBus} from '@/shared/eventbus'
import {MeterTypeService} from '@/services/MeterTypeService'
import {Manufacturers} from '@/classes/Manufacturer'
import {TariffService} from '@/services/TariffService'
import {ClusterService} from '@/services/ClusterService'
import {CityService} from '@/services/CityService'

export default {
    name: 'AddCustomer',
    components: {Widget},
    data() {
        return {
            customer: {},
            show: true,
            meterTypeService: new MeterTypeService(),
            meterManufacturerService : new Manufacturers(),
            meterTariffService: new TariffService(),
            clusterService: new ClusterService(),
            cityService: new CityService(),
        }
    },
    beforeMount() {
        this.meterTypeService.getMeterTypes()
        this.meterManufacturerService.getList()
        this.meterTariffService.getTariffs()
        this.clusterService.getClusters()
        this.cityService.getCities()
    },
    mounted() {
        EventBus.$on('addNewCustomer', this.setVisible)
    },
    methods: {
        setVisible(){
            this.show = true
        },
    },
    computed: {
        citiesInCluster() {
            if(this.customer.cluster && this.cityService.cities.length >0) {
                return this.cityService.cities.filter((city) => city.cluster_id === this.customer.cluster)
            }
            return []
        }
    }


}
</script>