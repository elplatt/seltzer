// Your JSON endpoints for Membership income and Expected membership income data
const membershipIncomeEndpoint = 'service.php?endpoint=monthly_payments';
const expectedMembershipIncomeEndpoint = 'service.php?endpoint=monthly_payments_due';

// Fetch data from the JSON endpoints
async function fetchData() {
    const membershipResponse = await fetch(membershipIncomeEndpoint);
    const expectedMembershipResponse = await fetch(expectedMembershipIncomeEndpoint);

    const membershipData = await membershipResponse.json();
    const expectedMembershipData = await expectedMembershipResponse.json();

    return { membershipData, expectedMembershipData };
}

// Process and format the data for Chart.js
function prepareData(data) {
    const labels = data.map(item => `${item.year}-${item.month}`);
    const membershipIncome = data.map(item => item.amount);
    return { labels, membershipIncome };
}

function calculateCumulativeIncome(monthlyIncome) {
    for (let i = 1; i < monthlyIncome.length; i++) {
        monthlyIncome[i]['amount'] += monthlyIncome[i-1]['amount'];
    }
    return monthlyIncome;
  }

// Create the line chart
async function createLineChart(container, chart_title, cumulative = false) {
    const data = await fetchData();
    if (cumulative == true) {
        preparedMembershipData = prepareData(calculateCumulativeIncome(data.membershipData));
        preparedExpectedMembershipData = prepareData(calculateCumulativeIncome(data.expectedMembershipData));
    } else {
        preparedMembershipData = prepareData(data.membershipData);
        preparedExpectedMembershipData = prepareData(data.expectedMembershipData);
    }
    const ctx = document.getElementById(container).getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: preparedMembershipData.labels,
            datasets: [
                {
                    label: 'Membership Income',
                    data: preparedMembershipData.membershipIncome,
                    borderColor: '#3498db', // Blue
                    fill: false,
                },
                {
                    label: 'Expected Membership Income',
                    data: preparedExpectedMembershipData.membershipIncome,
                    borderColor: '#2ecc71', // Green
                    fill: false,
                },
            ],
        },
        options: {
            responsive: true,
            scales: {
                x: {
                    title: {
                        display: true,
                        text: 'Date',
                    },
                },
                y: {
                    title: {
                        display: true,
                        text: 'Amount',
                    },
                },
            },
            plugins: {
                title: {
                  display: true,
                  text: chart_title,
                }
              }
        },
    });
}

async function plan_pie_chart() {
    const plan_data = await (await fetch('api.php?endpoint=plan_distribution')).json();
    const CHART_COLORS = {
        yellow: 'rgb(255, 205, 86)',
        green: 'rgb(75, 192, 192)',
        blue: 'rgb(54, 162, 235)',
        purple: 'rgb(153, 102, 255)',
        grey: 'rgb(201, 203, 207)',
        red: 'rgb(255, 99, 132)',
        orange: 'rgb(255, 159, 64)',
      };
    const data = {
        labels: plan_data.map(item => item.name),
        datasets: [
          {
            label: 'Dataset 1',
            data: plan_data.map(item => item.count),
            backgroundColor: Object.values(CHART_COLORS),
          }
        ]
      };
    console.log(data)
    const config = {
        type: 'pie',
        data: data,
        options: {
          responsive: true,
          plugins: {
            legend: {
              position: 'top',
            },
            title: {
              display: true,
              text: 'Active Plans'
            }
          }
        },
      };
      const ctx = document.getElementById('plan-distribution').getContext('2d');
      new Chart(ctx, config);
}

// Call the function to create the line chart
createLineChart('memberships-monthly', 'Memberships');
createLineChart('memberships-monthly-cumulative', 'Cumulative Memberships', true);
plan_pie_chart();
