
############################################################################################################################################################################

# Mixed Integer Linear Program for Mini-grid Dispatch-- Version 4.0 # Miners Simple Uganda Model
# Developed by:
# Tatiana González Grandón (Inensus GmbH, HU Berlin) tg@inensus.com
# Vivien Barnier           (Inensus GmbH)            vb@inensus.com

# This program only works with any time interval tau
##########################################################################################################################################################################

# Built-in libraries:
import os.path
from pprint import pprint

# Third-party libraries:
import pandas as pd
import numpy as np
import time
import math as mt
from pyomo.environ import *
# from pyomo.environ import Set, SetOf, Var, Param, Constraint, Objective, AbstractModel, NonNegativeReals, Reals, Binary, minimize, RangeSet, Expression
from pyomo.opt import SolverFactory
from logger.logger import Logger
# Ad-hoc libraries:
import utils_NO
from communication.socket_client import SocketClient


def create_model():
    model = AbstractModel(name="Miners_1")

    # Define sets
    # Timesteps
    model.T = Set(ordered=True)
    # Timestep
    model.tau = Param(within=Reals)

    # Crypto Miner
    # profit of crypto miners (USD/TH)
    model.p_mi = Param(within=NonNegativeReals)
    # Rated power of miner [kW]
    model.crypto_max = Param(within=NonNegativeReals)
    # Slope of miner's efficiency curve [TH/s/kW]
    model.m_mi = Param(within=NonNegativeReals)
    # Intercept coefficient linear miner's efficiency curve [TH/s/kW]
    model.b_mi = Param(within=Reals)
    # Numer of seconds in 1 hour time resolution [s]
    model.K_mi = Param(within=NonNegativeIntegers)
    # minimum running limit for miner
    model.crypto_min = Param(within=NonNegativeReals)

    # Epsilon Costs
    model.c_pv = Param(within=Reals)  # Marginal cost of PV
    model.c_bess = Param(within=Reals)  # BESS energy value
    model.q_a = Param(within=NonNegativeReals)  # multiplier of demand
    # model.q_b = Param(within=NonNegativeReals)                                       #  multiplier of demand

    # Diesel parameters
    # Rated capacity of diesel generator
    model.E_diesel = Param(within=NonNegativeReals)
    model.c_f = Param(within=NonNegativeReals)  # Cost of diesel (eur/L)
    # Diesel Genset start-up cost
    model.STC_dg = Param(within=NonNegativeReals)
    # Min Running Time for diesel Genset
    model.mrt = Param(within=NonNegativeReals)
    # minimum resting time before turning on genset
    model.rt = Param(within=NonNegativeReals)
    # intercept coefficient of the fuel curve. (L/hr/kW)
    model.b_dg = Param(within=NonNegativeReals)
    # slope coefficient of the fuel curve. (L/hr/kW)
    model.m_dg = Param(within=NonNegativeReals)
    # model.c_nse = Param(within=NonNegativeReals)                                     #  cost of non-served energy
    # Average efficiency for diesel generator
    model.eta_dg = Param(within=NonNegativeReals)
    # Lower limit for diesel dispatch in kW
    model.lower_limit_diesel = Param(within=NonNegativeReals)
    # model.mu_dg = Param(within=NonNegativeReals)                                     # Cycle cost penalty for the genset (eur/cycle)
    # Maximum lifetime of the genset (hours)
    model.l_dg_max = Param(within=NonNegativeReals)

    # Battery parameters
    # Max state of charge for BESS (kWh)
    model.SOC_max = Param(within=NonNegativeReals)
    model.SOC_min = Param(within=NonNegativeReals)  # MIN SoC (kWh)
    # self-discharge rate of the battery
    model.sigma_bess = Param(within=NonNegativeReals)
    model.L_disch = Param(within=NonNegativeReals)  # Max discharge limit (kW)
    # Min charge limit/ max consumption of battery (kW)
    model.L_char = Param(within=NonNegativeReals)
    # Average charging efficiency for Bess
    model.eta_char = Param(within=NonNegativeReals)
    # Average discharging efficiency for Bess
    model.eta_disc = Param(within=NonNegativeReals)
    # initial state of charge (%)
    model.SOC_ini = Param(within=NonNegativeReals)

    # PV Inverter parameters
    # Nominal power of PV inverter (kW)
    model.E_pv_inv = Param(within=NonNegativeReals)
    # average efficiency of PV inverter
    model.eta_pv_inv = Param(within=NonNegativeReals)

    # Battery inverter/rectifier
    # Nominal power of Bess Inverter-rectifier (kW)
    model.E_ir = Param(within=NonNegativeReals)
    # average efficiency of Bess inverter
    model.eta_bess_inv = Param(within=NonNegativeReals)
    # average efficiency of rectifier
    model.eta_rect = Param(within=NonNegativeReals)

    # Maintenance Parameters
    # cost coefficient for diesel maintenance (eur)
    model.mc_dg = Param(within=NonNegativeReals)
    # critical time value for diesel maintenance (min)
    model.t_dg = Param(within=NonNegativeReals)
    # cost coefficient for BESS maintenance (eur)
    model.m_bess = Param(within=NonNegativeReals)

    # Define Time series parameters
    # Customer's Load Time series (kWh)
    model.D = Param(model.T, within=NonNegativeReals)
    # max generation PV time series (kW)
    model.pv_max = Param(model.T, within=NonNegativeReals)
    # Electricity price time series ($/kWh)
    model.p_load = Param(model.T, within=NonNegativeReals)

    # Define Positive Variables
    # Battery charge at time t (kW)
    model.e_Bess_char = Var(model.T, within=NonNegativeReals)
    # Electricity output of PV at time t (kW)
    model.e_pv = Var(model.T, within=NonNegativeReals)
    # Electricity output of DG at time t (kW)
    model.e_dg = Var(model.T, within=NonNegativeReals)
    # Battery discharge at time t (kW)
    model.e_Bess_disch = Var(model.T, within=NonNegativeReals)
    # State of charge at time t (not in percentage, in kWh)
    model.SOC = Var(model.T, within=NonNegativeReals)
   # model.e_nse = Var(model.T, within=NonNegativeReals)                              # Not supplied electricity
    # Number of expended lifecycles for the genset at time t (hours?) ? TODO: unit ! hours ?
    model.L_dg = Var(model.T, within=NonNegativeReals)

    # Define binary variables
    # Discharge of BEss =1, 0= no discharge of Bess at this time
    model.u_t = Var(model.T, within=Binary)
    # charge of BEss =1, 0= no charge of Bess at this time
    model.v_t = Var(model.T, within=Binary)
    # Diesel Gen is On =1, 0=Diesel Gen is off
    model.w_t = Var(model.T, within=Binary)
    # Diesel Gen starts to run at this time=1, 0=other case
    model.r_t = Var(model.T, within=Binary)
    # Diesel Gen is stopped at this time=1, 0= other case
    model.s_t = Var(model.T, within=Binary)
    # Miner is on at this time=1, 0= other case
    model.m_t = Var(model.T, within=Binary)

    # Last variable /Input
    # Final Demand to consider
    model.DD_t = Var(model.T, within=NonNegativeReals)
    # Total Electricity use of miners (kW)
    model.e_m = Var(model.T, domain=NonNegativeReals)

    # Define the rules for the objective function

    def miner_profit(m, t):
        """Computes the profit from the linear efficiency curve of crypto mining"""
        return m.e_m[t]*m.tau*m.K_mi*m.p_mi*m.m_mi + m.p_mi*m.b_mi*(m.crypto_max*m.tau*m.m_t[t])

    # def maint_cost(m,t):
        # return m.c_bess * m.u_t[t]
        # only maintenance of the battery discharge

    def diesel_cost(m, t):  # (D)
        """Computes the cost from the linear consumption of the diesel generator"""
        return m.e_dg[t] * m.tau * m.c_f * m.m_dg + m.c_f * m.b_dg * (m.E_diesel * m.tau*m.w_t[t])
        # change appropiate variables kW -> kWh [tati] added w_t

    def start_cost(m, t):
        """Diesel generator start-up costs"""
        return m.STC_dg * m.r_t[t]  # x = r_t

    def pv_cost(m, t):
        """PV power used to supply the load and charge the battery. Marginal cost is tending to zero"""
        return m.c_pv * m.e_pv[t]*m.tau  # x = e_pv and kW -> kWh

    def SOC_operation(m, t):
      #  """To charge as much power in the BESS for the next period's operational schedule and minimize use of diesel"""
        return m.c_bess * m.tau * m.SOC[t] / m.SOC_max  # x = SOC and kW -> kWh

    def Demandprice(m, t):
        return m.D[t] * m.p_load[t]*m.q_a

    def objective(m):
        return sum(diesel_cost(m, t) + start_cost(m, t) + pv_cost(m, t) + SOC_operation(m, t) - Demandprice(m, t) - miner_profit(m, t) for t in m.T)
        # return sum(- maint_cost(m, t) - diesel_cost(m,t) - start_cost(m,t) - pv_cost(m,t) - nse_cost(m,t) - Lifecycle_cost(m,t) + SOC_operation(m,t) + Demandprice(m,t)  for t in m.T)
        # return sum(maint_cost(m, t) + diesel_cost(m,t) + start_cost(m,t) + pv_cost(m,t) + nse_cost(m,t) + Lifecycle_cost(m,t) - SOC_operation(m,t) - Demandprice(m,t)  for t in m.T)
        #  verify SOC_operation if it should take a - or + sign

    # Define constraints in the order of page 7 of the pdf
    def constraint_kirchhoff(m, t):
        # +- m.e_nse[t]
        return m.D[t]*m.q_a * (1/m.tau) == m.e_pv[t] * m.eta_pv_inv + m.e_Bess_disch[t] * m.eta_disc * m.eta_bess_inv - (m.e_Bess_char[t]/m.eta_rect*m.eta_char) + m.e_dg[t] - m.e_m[t]
        # return m.D[t] * 4 == m.e_pv[t] * m.eta_pv_inv + m.e_Bess_disch[t] * m.eta_disc * m.eta_bess_inv -(m.e_Bess_char[t]/m.eta_rect*m.eta_char) + m.e_dg[t] # + m.e_nse[t]
        # Demand from kWh -> kW

    def final_demand(m, t):
        return m.DD_t[t] == m.D[t]*m.q_a*(1/m.tau)  # kW

    def constraint_pv_limit(m, t):
        return m.e_pv[t] <= m.pv_max[t]  # kW

    def constraint_pv_inverter(m, t):
        return m.eta_pv_inv * m.e_pv[t] <= m.E_pv_inv  # kW

    def constraint_diesel_upper_limit(m, t):
        return m.e_dg[t] <= m.E_diesel * m.w_t[t]  # kW

    def constraint_diesel_lower_limit(m, t):
        return m.e_dg[t] >= m.lower_limit_diesel * m.w_t[t]  # kW

    def constraint_diesel_binary_restriction(m, t):
        if t == m.T.first():
            return m.w_t[t] == m.r_t[t] - m.s_t[t]
        else:
            t_prev = m.T.prev(t)
            return m.w_t[t] - m.w_t[t_prev] == m.r_t[t] - m.s_t[t]

    def constraint_diesel_duration(m, t):
        """Define a loop until MRT"""
        return m.r_t[t] + sum(m.s_t[t+i] for i in RangeSet(min(m.mrt-1, m.T.last()-t))) <= 1.

    def constraint_diesel_resting(m, t):
        """Define a loop until RT"""
        return m.s_t[t] + sum(m.r_t[t+i] for i in RangeSet(min(m.rt-1, m.T.last()-t))) <= 1.

    def constraint_diesel_lifecycle_lower(m, t):
        return m.L_dg[t] == sum(m.w_t[tt] for tt in m.T if tt <= t) * m.tau

   # battery's physics:

    def constraint_battery_dynamics(m, t):
        """Dynamics of the battery state of Charge"""
        if t == m.T.first():
            # kW -> kWh
            return m.SOC[t] == m.SOC_ini * (1. - m.sigma_bess) + (m.e_Bess_char[t] - m.e_Bess_disch[t]) * m.tau
        else:
            t_prev = m.T.prev(t)
            return m.SOC[t] == m.SOC[t-1] * (1. - m.sigma_bess) + (m.e_Bess_char[t] - m.e_Bess_disch[t]) * m.tau

    def maximal_discharge(m, t):
        """Storage battery maximal generation/discharge limit in each period"""
        return m.e_Bess_disch[t] <= m.L_disch * m.u_t[t]
        # return m.e_Bess_disch[t] * m.tau <= m.L_disch * m.u_t[t]
        # TODO Merge this Constraint together with Inverter-rectifier Limits

    def maximal_charge(m, t):
        """Storage battery maximal generation/discharge limit in each period"""
        return m.e_Bess_char[t] <= m.L_char * m.v_t[t]
        # return m.e_Bess_char[t] * m.tau <= m.L_char * m.v_t[t]

    def battery_logic(m, t):
        """The battery cannot charge and discharge at the same time in each period t"""
        return m.u_t[t] + m.v_t[t] <= 1.

    def constraint_SoC_max(m, t):
        return m.SOC[t] <= m.SOC_max

    def constraint_SoC_min(m, t):
        return m.SOC[t] >= m.SOC_min

    def constraint_inverter_limits_charge(m, t):
        return m.e_Bess_char[t] / m.eta_char <= m.E_ir * m.v_t[t]

    def constraint_inverter_limits_discharge(m, t):
        return m.e_Bess_disch[t] * m.eta_disc * m.eta_bess_inv <= m.E_ir * m.u_t[t]

   # MINER CAPACITY

    def miners_upper_limit(m, t):
        return m.e_m[t] <= m.crypto_max * m.m_t[t]  # kW

    # def miners_lower_limit(m,t):
     #   return m.e_m[t] >= m.crypto_min* m.m_t[t]

    # Define the constraints
    model.balance = Constraint(model.T, rule=constraint_kirchhoff)
    model.demandfinal = Constraint(model.T, rule=final_demand)
    model.battery_dynamics = Constraint(
        model.T, rule=constraint_battery_dynamics)
    model.battery_max_discharge = Constraint(model.T, rule=maximal_discharge)
    model.battery_max_charge = Constraint(model.T, rule=maximal_charge)
    model.battery_logic = Constraint(model.T, rule=battery_logic)
    model.battery_SoC_max = Constraint(model.T, rule=constraint_SoC_max)
    model.battery_SoC_min = Constraint(model.T, rule=constraint_SoC_min)

    model.inverter_battery_charge = Constraint(
        model.T, rule=constraint_inverter_limits_charge)
    model.inverter_battery_discharge = Constraint(
        model.T, rule=constraint_inverter_limits_discharge)

    model.pv_limit = Constraint(model.T, rule=constraint_pv_limit)
    model.pv_inverter_limit = Constraint(model.T, rule=constraint_pv_inverter)

    model.diesel_upper_limit = Constraint(
        model.T, rule=constraint_diesel_upper_limit)
    model.diesel_lower_limit = Constraint(
        model.T, rule=constraint_diesel_lower_limit)
    model.diesel_binary = Constraint(
        model.T, rule=constraint_diesel_binary_restriction)
    model.diesel_duration = Constraint(
        model.T, rule=constraint_diesel_duration)
    model.diesel_resting = Constraint(model.T, rule=constraint_diesel_resting)
    model.lifecycle_diesel_lower = Constraint(
        model.T, rule=constraint_diesel_lifecycle_lower)

    # Constraints for the miners
    model.cryptos_upper_limit = Constraint(model.T, rule=miners_upper_limit)
    #model.cryptos_lower_limit = Constraint(model.T, rule=miners_lower_limit)

    # Define the expressions for objective:
    model.diesel_cost = Expression(model.T, rule=diesel_cost)
    model.start_cost = Expression(model.T, rule=start_cost)
    model.pv_cost = Expression(model.T, rule=pv_cost)
    model.Demandprice = Expression(model.T, rule=Demandprice)
    #model.nse_cost = Expression(model.T, rule=nse_cost)
    model.SOC_operation = Expression(model.T, rule=SOC_operation)
    #model.maint_cost = Expression(model.T, rule=maint_cost)
    model.miner_profits = Expression(model.T, rule=miner_profit)

    model.Obj = Objective(rule=objective, sense=minimize)  # minimize

    return model


def solve(instance, solvername):
    """Define the solve function and its parameters (solver name, pprinting results, loading results)"""
    solver = SolverFactory(solvername)
    # N (22.09): tee=True helps with solver trouble by displaying what the solver is doing.
    results = solver.solve(instance, tee=True)

    results_json = results.json_repn()
    problem_description = results_json['Problem'][0]
    solver_description = results_json['Solver'][0]

    print("Problem description:")
    pprint(problem_description)
    print("Solver description:")
    pprint(solver_description)

    return results

if __name__ == "__main__":

    dirname = os.path.dirname(__file__)
    filename_input = os.path.join(dirname, 'Data', 'Data_input_Miners.xlsx')
    filename_output = os.path.join(dirname, 'Data', 'Data_output_Miners.xlsx')
    start_time = time.time()
    steps = 192  # input('Please enter the number of timesteps:  ')

    try:
        data = utils_NO.load_minigrid_data(filename_input, steps)
        Logger.log("Data successfully loaded from the input excel sheet.")
    except Exception as e:
        Logger.error("Error while loading data: {}".format(e))
        raise Exception("Error while loading data: {}".format(e))

    try:

        model = create_model()
        Logger.log("Model successfully created.")
        instance = model.create_instance(data)
        Logger.log("Model instance successfully created.")
    except Exception as e:
        Logger.error("Error while creating the model: {}".format(e))
        raise Exception("Error while creating the model: {}".format(e))

    results = solve(instance, 'cbc')
    result = {}

    try:

        assert str(results.solver.status) == "ok"
        assert str(results.solver.termination_condition) == "optimal"
        Logger.log("Solver status: " + str(results.solver.status))
        Logger.log("Solver termination condition: " +
                   str(results.solver.termination_condition))

    # If all is good (solver status OK and termination condition optimal), then write results into excel file:
        utils_NO.dump_minigrid_data(filename_output, instance, data)
        result['excel_path'] = os.path.abspath(filename_output)
        result['success'] = 1
    except AssertionError as e:
        result['excel_path'] = None
        result['success'] = 0
        Logger.error(f'Assertion error occured.  {str(e)}')
    except Exception as e:
        result['excel_path'] = None
        result['success'] = 0
        result['error'] = str(e)
        Logger.error(f'Unexpected error occured.  {str(e)}')

    result['solver_status'] = str(results.solver.status)
    result['termination_condition'] = str(results.solver.termination_condition)
    end_time = time.time()
    Logger.log('Sending results to socket server')

