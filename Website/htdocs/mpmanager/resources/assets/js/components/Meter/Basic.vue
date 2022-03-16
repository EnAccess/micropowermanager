<template>
    <widget>
        <div class="meter-overview-card">
            <div class="md-subheading">{{ $tc('words.basic') }}</div>
            <div class="meter-overview-detail" v-if="meter!==null && meter.loaded===true">
                <div class="md-layout">
                    <div class="md-layout-item">{{ $tc('words.register', 2) }}</div>
                    <div class="md-layout-item">{{ meter.registered }}</div>
                </div>
                <div class="md-layout">
                    <div class="md-layout-item">{{ $tc('words.owner') }}</div>
                    <div class="md-layout">
                        <div class="md-layout-item">
                            <div v-if="!showOwnerEdit">
                                <a href="javascript:void(0)" @click="navigateOwner(meter.owner.id)">
                                    {{ meter.owner.name }}
                                    {{ meter.owner.surname }}
                                </a>
                                <span style="cursor:pointer" @click="showOwnerEdit = true"><md-icon>edit</md-icon></span>
                            </div>

                            <div class="md-layout-item" v-if="showOwnerEdit">
                                <md-autocomplete
                                    v-model="customerSearchTerm"
                                    :md-options="searchNames"
                                    @md-changed="searchFor"
                                    @md-opened="searchFor"
                                    @md-selected="selectCustomer"
                                >
                                    <label>{{ $tc('phrases.newOwner') }}</label>
                                    <template slot="md-autocomplete-item" slot-scope="{ item }">{{
                                            item.name
                                        }}
                                    </template>
                                </md-autocomplete>
                                <md-button v-if="showOwnerEdit" class="md-icon-button"
                                           @click="saveNewOwner()">
                                    <md-icon class="md-primary">save</md-icon>
                                </md-button>
                                <md-button class="md-icon-button" @click="closeOwnerEdit()">
                                    <md-icon class="md-accent">cancel</md-icon>
                                </md-button>
                            </div>
                        </div>


                    </div>

                </div>
                <div class="md-layout">
                    <div class="md-layout-item">{{ $tc('phrases.totalRevenue') }}</div>
                    <div class="md-layout-item">
                        <div v-if="meter.totalRevenue">{{ readable(meter.totalRevenue) }}
                            {{ $store.getters['settings/getMainSettings'].currency }}
                        </div>
                        <div v-else>{{ $tc('phrases.noData') }}</div>
                    </div>
                </div>
                <div class="md-layout">
                    <div class="md-layout-item">{{ $tc('phrases.lastPayment') }}</div>
                    <div class="md-layout-item">{{ $tc('phrases.3daysAgo') }}</div>
                </div>
            </div>
        </div>
    </widget>
</template>

<script>
import Widget from '../../shared/widget'
import { currency } from '../../mixins/currency'
import { PersonService } from '../../services/PersonService'
import { MeterParameterService } from '../../services/MeterParameterService'
export default {
    name: 'MeterBasic.vue',
    components: { Widget },
    mixins: [currency],
    props:{
        meter:{
            type:Object,
        },
    },
    data(){
        return{
            meterParameterService: new MeterParameterService(),
            personService: new PersonService(),
            showOwnerEdit: false,
            customerSearchTerm: '',
            searchTerm: '',
            newOwner: null,
            searchNames: [],
        }
    },
    methods:{
        saveNewOwner () {
            if (this.newOwner === null) {
                this.$swal({
                    type: 'error',
                    title: this.$tc('phrases.meterDetailNotify', 3),
                    text: this.$tc('phrases.meterDetailNotify2', 0)
                })
                return
            }
            this.$swal({
                type: 'success',
                title: this.$tc('phrases.meterDetailNotify2', 1),
                text: this.$tc('phrases.meterDetailNotify2', 3,
                    { newName: this.newOwner.name, name: this.meter.owner.name + ' ' + this.meter.owner.surname }),
                showCancelButton: true,
                confirmButtonText: this.$tc('words.confirm'),
                cancelButtonText: this.$tc('words.cancel'),
            }).then(result => {
                if (result.value) {
                    this.meterParameterService.update(this.meter.id, { personId: this.newOwner.id })
                        .then(response => {
                            if (response.status === 200) {
                                this.meter.owner = response.data.data.owner
                                this.showOwnerEdit = false
                                this.resetOwner()
                            } else {
                                this.$swal({
                                    type: 'error',
                                    title: this.$tc('phrases.meterDetailNotify'),
                                    text: this.$tc('phrases.meterDetailNotify', 2)
                                })
                            }
                        })
                }
            })
        },
        navigateOwner (ownerId) {
            this.$router.push('/people/' + ownerId)
        },
        searchFor (term) {
            if (term != undefined && term.length > 2) {
                this.personService.searchPerson({ params: { term: term, paginate: 0 } }).then(response => {
                    this.searchNames = []
                    for (let i in response.data.data) {
                        let person = response.data.data[i]
                        this.searchNames.push({
                            id: person.id,
                            name: person.name + ' ' + person.surname
                        })
                    }
                    this.hideSearch = false
                    return this.searchNames.map(x => ({
                        id: x.id,
                        name: x.name,
                        toLowerCase: () => x.name.toLowerCase(),
                        toString: () => x.name
                    }))
                    // return this.searchNames;
                })
            } else {
                this.hideSearch = true
            }
        },
        setOwner (owner) {
            this.newOwner = owner
        },
        closeOwnerEdit () {
            this.resetOwner()
            this.showOwnerEdit = false
        },
        selectCustomer (c) {
            this.customerSearchTerm = c.name
            this.newOwner = c
        },
        resetOwner () {
            this.searchTerm = ''
            this.hideSearch = true
            this.newOwner = null
            this.searchNames = []
        },
    }
}
</script>

<style scoped>
</style>
