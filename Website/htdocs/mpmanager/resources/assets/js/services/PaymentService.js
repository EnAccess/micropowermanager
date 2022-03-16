import Repository from '../repositories/RepositoryFactory'
import { ErrorHandler } from '../Helpers/ErrorHander'
import i18n from '../i18n'

export class PaymentService {
    constructor () {
        this.repository = Repository.get('paymentHistory')
        this.paymentDetailData = []
        this.chartData = [[i18n.tc('words.month'), i18n.tc('words.sale')]]
        this.flow = []
        this.monthNames= [
            'Jan',
            'Feb',
            'Mar',
            'Apr',
            'May',
            'June',
            'July',
            'Aug',
            'Sept',
            'Oct',
            'Nov',
            'Dec'
        ]
    }

    async getPaymentDetail(personId, period){
        try {
            let response = await this.repository.getPaymentDetail(personId, period)
            if(response.status === 200){
                this.fillPaymentDetailChartData(response.data)
                return response.data
            }else{
                return new ErrorHandler(response.error, 'http', response.status)
            }
        }catch (e) {
            return new ErrorHandler(e.response.data.message, 'http')
        }

    }
    async getPaymentFlow (personId) {
        try {
            let response = await this.repository.getFlow(personId)
            if(response.status === 200){
                this.fillPaymentFlowChartData(response.data)
            }else{
                return new ErrorHandler(response.error, 'http', response.status)
            }
        }catch (e) {
            return new ErrorHandler(e.response.data.message, 'http')
        }

    }
    async getPeriod(personId){
        try {
            let response = await this.repository.getPeriod(personId)
            if(response.status === 200){
                return response.data.data
            }else{
                return new ErrorHandler(response.error, 'http', response.status)
            }
        }catch (e) {
            return new ErrorHandler(e.response.data.message, 'http')
        }
    }
    async getDebt(personId){
        try {
            let response = await this.repository.getDebt(personId)
            if(response.status === 200){
                return response.data.data
            }else{
                return new ErrorHandler(response.error, 'http', response.status)
            }
        }catch (e) {
            return new ErrorHandler(e.response.data.message, 'http')
        }
    }
    fillPaymentFlowChartData(paymentFlowData){
        this.flow = []
        paymentFlowData.forEach((i)=>{
            this.flow.push(parseInt(paymentFlowData[i]))
            this.chartData.push([
                this.monthNames[i],
                parseInt(paymentFlowData[i])
            ])
        })
        return this.chartData
    }
    fillPaymentDetailChartData(paymentDetail){
        this.paymentDetailData = [[i18n.tc('words.period'), i18n.tc('words.energy'), i18n.tc('phrases.accessRate'), i18n.tc('phrases.loanRate')]]
        for (let i in paymentDetail) {
            let chartDataItem = [
                i,
                'energy' in paymentDetail[i] ? parseInt(paymentDetail[i]['energy']) : 0,
                'access rate' in paymentDetail[i] ? parseInt(paymentDetail[i]['access rate']) : 0,
                'loan rate' in paymentDetail[i] ? parseInt(paymentDetail[i]['loan rate']) : 0,
            ]
            this.paymentDetailData.push(chartDataItem)
        }
        return this.paymentDetailData
    }
}
