# 🔐 LoanSecure Default Prediction System

### AI-Powered Loan Risk Assessment & Prediction Platform

LoanSecure Default Prediction System is a **Data Science and Machine Learning-based web application** designed to assist banks and financial institutions in evaluating loan applications.

The system analyzes applicant financial and personal information, performs complete data preprocessing and exploratory data analysis, trains multiple Machine Learning models, selects the best-performing model, and provides loan prediction through an interactive Flask web application.

---

## 📌 Project Overview

Loan approval is an important financial decision. Traditional loan evaluation can require significant manual effort and may not always provide consistent risk assessment.

**LoanSecure** uses Machine Learning to analyze historical loan application data and identify patterns associated with loan approval and rejection.

The platform provides:

* Loan approval prediction
* Customer risk assessment
* Approval probability
* Risk score
* Financial analytics
* Interactive dashboard
* Data visualization
* Machine Learning model comparison

---

## 🎯 Objectives

* Predict loan approval using Machine Learning.
* Analyze customer financial information.
* Identify important factors affecting loan decisions.
* Perform complete data cleaning and preprocessing.
* Compare multiple Machine Learning algorithms.
* Select and save the best-performing model.
* Provide predictions through a Flask web application.
* Present financial insights through an attractive dashboard.

---

## ✨ Key Features

### 📊 Data Science

* Dataset loading and inspection
* Missing value detection and handling
* Duplicate detection and removal
* Data type correction
* Inconsistent data handling
* Outlier detection
* Feature engineering
* Feature selection
* Data encoding
* Feature scaling
* Exploratory Data Analysis

### 🤖 Machine Learning

The project trains and compares multiple algorithms:

* Logistic Regression
* Decision Tree
* Random Forest
* K-Nearest Neighbors
* Support Vector Machine
* Naive Bayes
* Gradient Boosting

### 📈 Model Evaluation

Models are evaluated using:

* Accuracy
* Precision
* Recall
* F1 Score
* ROC-AUC Score
* Confusion Matrix
* Classification Report
* Cross Validation

The best-performing model is selected based on the evaluation results and saved for prediction.

---

## 🏦 Application Features

### Loan Prediction

Users can enter information such as:

* Gender
* Marital Status
* Dependents
* Education
* Self Employment Status
* Applicant Income
* Coapplicant Income
* Loan Amount
* Loan Amount Term
* Credit History
* Property Area

The system processes the input and provides:

* Loan Prediction
* Approval Probability
* Risk Score
* Risk Category
* Prediction Confidence

---

## 📊 Analytics Dashboard

The dashboard provides a visual overview of the loan dataset and prediction results.

### KPI Cards

* Total Applications
* Approved Loans
* Rejected Loans
* Approval Rate
* Average Applicant Income
* Average Loan Amount

### Visualizations

* Loan Approval Distribution
* Loan Rejection Distribution
* Income Analysis
* Loan Amount Analysis
* Credit History Analysis
* Risk Distribution
* Property Area Analysis
* Customer Analytics

---

## 🎨 User Interface

The application uses a modern **Banking & FinTech-inspired UI**.

Features include:

* Responsive design
* Modern dashboard
* Interactive cards
* Attractive charts
* Animated components
* Smooth transitions
* Professional navigation
* Prediction result visualization
* Risk indicators
* Mobile-friendly layout
* Dark/Light theme support

---

## 🛠️ Technology Stack

### Programming Languages

* Python
* HTML5
* CSS3
* JavaScript

### Backend

* Flask

### Data Processing

* NumPy
* Pandas

### Machine Learning

* Scikit-Learn

### Data Visualization

* Matplotlib
* Seaborn
* Chart.js

### Model Serialization

* Joblib
* Pickle

---

## 📂 Project Structure

```text
LoanSecure/
│
├── app.py
├── model.pkl
├── requirements.txt
├── README.md
│
├── dataset/
│   └── loan_prediction.csv
│
├── notebooks/
│   └── analysis.ipynb
│
├── preprocessing/
│   ├── clean_data.py
│   ├── preprocessing.py
│   └── feature_engineering.py
│
├── models/
│   ├── train_model.py
│   ├── evaluate_model.py
│   └── predict.py
│
├── utils/
│   └── helper.py
│
├── templates/
│   ├── index.html
│   ├── predict.html
│   ├── dashboard.html
│   ├── result.html
│   ├── about.html
│   └── contact.html
│
└── static/
    ├── css/
    │   └── style.css
    │
    ├── js/
    │   └── script.js
    │
    └── images/
```

---

# 🔄 Machine Learning Workflow

```text
Loan Prediction Dataset
          ↓
Data Loading
          ↓
Data Inspection
          ↓
Data Cleaning
          ↓
Missing Value Handling
          ↓
Duplicate Removal
          ↓
Outlier Detection
          ↓
Exploratory Data Analysis
          ↓
Feature Engineering
          ↓
Encoding
          ↓
Feature Scaling
          ↓
Train-Test Split
          ↓
Model Training
          ↓
Model Evaluation
          ↓
Model Comparison
          ↓
Best Model Selection
          ↓
Model Saving
          ↓
Flask Deployment
          ↓
Loan Prediction
```

---

# 📁 Dataset

The project uses a **Loan Prediction Dataset** containing information about loan applicants.

Important attributes include:

| Feature           | Description                |
| ----------------- | -------------------------- |
| Loan_ID           | Unique loan application ID |
| Gender            | Applicant gender           |
| Married           | Marital status             |
| Dependents        | Number of dependents       |
| Education         | Education level            |
| Self_Employed     | Employment status          |
| ApplicantIncome   | Applicant income           |
| CoapplicantIncome | Coapplicant income         |
| LoanAmount        | Requested loan amount      |
| Loan_Amount_Term  | Loan repayment term        |
| Credit_History    | Credit history             |
| Property_Area     | Property location          |
| Loan_Status       | Loan approval status       |

### Target Variable

```text
Loan_Status
```

Where:

```text
Y = Loan Approved
N = Loan Rejected
```

---

# 🧹 Data Preprocessing

The dataset goes through several preprocessing operations before Machine Learning.

### 1. Missing Values

Missing values are detected and handled using appropriate techniques such as:

* Mean
* Median
* Mode

### 2. Duplicate Records

Duplicate records are identified and removed to improve data quality.

### 3. Outliers

Numerical features are analyzed using statistical methods such as the **IQR method** and visualization techniques.

### 4. Categorical Data

Categorical variables are converted into numerical format using suitable encoding techniques.

### 5. Feature Scaling

Numerical features are scaled where required to improve model performance.

---

# 📊 Exploratory Data Analysis

EDA is performed to understand relationships and patterns in the dataset.

Visualizations include:

* Histograms
* Bar charts
* Count plots
* Box plots
* Pie charts
* Correlation heatmap
* Pair plots
* Distribution plots

EDA helps identify factors that may influence loan approval and customer risk.

---

# 🧠 Machine Learning Models

The following models are trained and compared:

### Logistic Regression

Used as a baseline classification algorithm for predicting loan approval.

### Decision Tree

Creates decision rules based on applicant features.

### Random Forest

Combines multiple decision trees to improve prediction performance.

### K-Nearest Neighbors

Predicts the class based on similar data points.

### Support Vector Machine

Finds an optimal boundary between different classes.

### Naive Bayes

Uses probability-based classification.

### Gradient Boosting

Builds models sequentially to improve prediction performance.

---

# 📏 Model Evaluation

The models are compared using:

```text
Accuracy
Precision
Recall
F1 Score
ROC-AUC
Confusion Matrix
Cross Validation
```

The final model is selected based on overall performance rather than relying only on accuracy.

---

# ⚙️ Installation

## 1. Clone the Repository

```bash
git clone https://github.com/yourusername/LoanSecure.git
```

## 2. Open the Project

```bash
cd LoanSecure
```

## 3. Create Virtual Environment

```bash
python -m venv venv
```

## 4. Activate Virtual Environment

### Windows

```bash
venv\Scripts\activate
```

### Linux / macOS

```bash
source venv/bin/activate
```

## 5. Install Dependencies

```bash
pip install -r requirements.txt
```

---

# ▶️ Running the Project

First train the Machine Learning model:

```bash
python models/train_model.py
```

After successful training, the trained model will be saved as:

```text
model.pkl
```

Then start the Flask application:

```bash
python app.py
```

Open the application in your browser:

```text
http://127.0.0.1:5000
```

---

# 📸 Screenshots

Add screenshots of your application here.

Recommended screenshots:

1. Home Page
2. Login Page
3. Dashboard
4. Loan Prediction Form
5. Prediction Result
6. Analytics Dashboard
7. Model Comparison
8. Risk Analysis

Example:

```text
docs/screenshots/
├── home.png
├── dashboard.png
├── prediction.png
├── result.png
└── analytics.png
```

---

# 🔮 Future Enhancements

The project can be further enhanced with:

* Explainable AI using SHAP or LIME
* Deep Learning models
* Real-time credit score integration
* Fraud detection
* Customer segmentation
* Automated loan recommendations
* PDF loan reports
* Email notifications
* Cloud deployment
* Mobile application
* Database integration
* Real-time banking API integration
* Advanced customer risk profiling

---

# ⚠️ Disclaimer

This project is developed for **educational, research, and demonstration purposes**.

Machine Learning predictions should not be treated as the sole basis for real-world financial or loan approval decisions. Real banking systems require additional regulatory, financial, legal, security, and credit-risk checks.

---

### Areas of Interest

* Data Science
* Machine Learning
* Data Analytics
* Python
* Flask
* Web Development

---

# ⭐ Acknowledgement

This project was developed as a Data Science and Machine Learning project to demonstrate the complete workflow from **raw data preprocessing to Machine Learning model deployment through a web application**.

---

# 📜 License

This project is intended for educational and academic purposes.
